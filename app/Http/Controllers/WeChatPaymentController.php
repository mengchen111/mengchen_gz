<?php

namespace App\Http\Controllers;

use App\Exceptions\WeChatPaymentException;
use App\Models\ItemType;
use App\Traits\WeChatPaymentTrait;
use Illuminate\Http\Request;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use Illuminate\Support\Facades\Log;
use App\Models\WechatOrder;
use Exception;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WeChatPaymentController extends Controller
{
    use WeChatPaymentTrait;

    protected $orderApp;
    protected $notifyUrl;

    public function __construct(Request $request)
    {
        $this->orderApp = new Application(config('wechat'));
        //$this->notifyUrl = env('APP_URL') . '/api/wechat/order/notification';
        $this->notifyUrl = 'http://admin-new.11majiang.com/api/wechat/order/notification';

        parent::__construct($request);
    }

    public function createOrder(Request $request)
    {
        $data = $this->validateCreateOrderRequest($request);

        //创建内部订单
        $order = $this->initializeOrder($data, $request);
        //return $order;

        //发起预支付请求
        try {
            $result = $this->preparePayment($order);
        } catch (Exception $exception) {
            //更新订单状态，将异常重新抛出
            $this->orderPreparationFailed($order, $exception->getMessage());
            throw $exception;
        }

        if ($request->trade_type === 'NATIVE') {
            return [
                'prepay_id' => $result->prepay_id,
                'qr_code' => $this->generateQrCodeStr($result->code_url),
            ];
        }

        return [
            'prepay_id' => $result->prepay_id,
        ];
    }

    protected function generateQrCodeStr($content)
    {
        return base64_encode(QrCode::format('png')->size(200)->generate($content));
    }

    protected function validateCreateOrderRequest(Request $request)
    {
        $this->validate($request, [
            'order_creator_type' => 'required|integer|in:1,2',
            'order_creator_id' => 'required|integer',
            'item_type_id' => 'required|exists:item_type,id',
            'item_amount' => 'required|integer|min:1',
            'trade_type' => 'required|string',
        ]);
        return $request->intersect([
            'order_creator_type', 'order_creator_id', 'item_type_id', 'item_amount', 'trade_type'
        ]);
    }

    protected function initializeOrder($data, $request)
    {
        //暂不检查，允许创建多个未支付的订单
        //$this->checkUnfinishedOrder($data['order_creator_type'], $data['order_creator_id']);

        $item = ItemType::find($data['item_type_id']);
        $data['out_trade_no'] = $this->createOutTradeNumber();
        $data['body'] = '梦晨网络-' . $item->name . '充值';
        $data['total_fee'] = $item->price * $data['item_amount'];
        $data['spbill_create_ip'] = $request->getClientIp();
        $data['order_status'] = 1;
        $data['item_delivery_status'] = 0;

        return WechatOrder::create($data);
    }

    protected function preparePayment($order)
    {
        $attributes = [
            'trade_type' => $order->trade_type,
            'body' => $order->body,
            //'detail' => $order->detail,   //预留
            'total_fee' => $order->total_fee,
            'spbill_create_ip' => $order->spbill_create_ip,
            'out_trade_no' => $order->out_trade_no,
            'notify_url' => $this->notifyUrl,
        ];

        $wechatOrder = new Order($attributes);
        $result = $this->orderApp->payment->prepare($wechatOrder);

        if ($result->return_code === 'SUCCESS') {
            if ($result->result_code === 'SUCCESS') {
                $this->orderPreparationSucceed($order, $result);
                return $result;
            }
            if ($result->result_code === 'FAIL') {
                $errMsg = $result->err_code . '|' . $result->err_code_des;
                $this->orderPreparationFailed($order, $errMsg);
                throw new WeChatPaymentException($errMsg);
            }
        }

        if ($result->return_code === 'FAIL') {
            $this->orderPreparationFailed($order, $result->return_msg);
            throw new WeChatPaymentException($result->return_msg);
        }

        throw new WeChatPaymentException('未知错误: ' . $result->toJson());
    }

    //更改订单状态为预支付失败
    protected function orderPreparationFailed($order, $msg)
    {
        $order->order_status = 3;
        $order->order_err_msg = $msg;
        $order->save();
    }

    protected function orderPreparationSucceed($order, $result)
    {
        $order->order_status = 2;
        $order->prepay_id = $result->prepay_id;

        if ($result->trade_type === 'NATIVE') {
            $order['code_url'] = $result->code_url;
        }

        $order->save();
    }

    protected function checkUnfinishedOrder($creatorType, $creatorId)
    {
        $orders = WechatOrder::where('order_creator_type', $creatorType)
            ->where('order_creator_id', $creatorId)
            ->whereIn('order_status', [1, 2])   //订单状态为已创建或未支付状态的订单
            ->get();
        if (!$orders->isEmpty()) {
            throw new WeChatPaymentException('存在未完成的订单，请支付或取消之前的订单之后再次尝试');
        }
        return true;
    }

    //创建订单号（内部订单号，非微信返回的交易id号）
    protected function createOutTradeNumber()
    {
        //随机数加上当前时间戳，生成md5的订单号
        return md5(mt_rand() . time());
    }

    //微信支付结果通知回调函数
    public function getNotification(Request $request)
    {
        Log::info('wechat', $request->toArray());
        return [
            'return_code' => 'SUCCESS',
        ];
    }

    //获取订单数据
    public function getOrder(Request $request, $orderId = null)
    {
        if (empty($orderId)) {
            return WechatOrder::paginate($this->per_page);
        }
        return WechatOrder::where('id', $orderId)->first();
    }

    //查询订单状态
    public function checkOrderStatus(Request $request, $outTradeNo)
    {
        $order = WechatOrder::where('out_trade_no', $outTradeNo)->firstOrFail();
        return [
            'status_code' => $order->order_status,
            'status_des' => $this->orderStatusMap[$order->order_status],
        ];
    }

    //公开的关单接口
    public function closeOrder(Request $request, WechatOrder $order)
    {
        if (in_array($order->order_status, [2, 5])) {
            $this->cancelOrder($order);
            return [
                'message' => '关单成功',
            ];
        }
        throw new WeChatPaymentException('只有预支付订单创建成功和支付失败的订单可以被关闭');
    }

    //取消订单
    protected function cancelOrder($order)
    {
        $result = $this->orderApp->payment->close($order->out_trade_no);

        if ($result->return_code === 'SUCCESS') {
            if ($result->result_code === 'SUCCESS') {
                $order->order_status = 6;
                $order->save();
                return true;
            }

            if ($result->result_code === 'FAIL') {
                throw new WeChatPaymentException($result->err_code . '|' . $result->err_code_des);
            }
        }

        if ($result->return_code === 'FAIL') {
            throw new WeChatPaymentException($result->return_msg);
        }

        throw new WeChatPaymentException('未知错误: ' . $result->toJson());
    }
}
