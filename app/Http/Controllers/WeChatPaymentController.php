<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Exceptions\WeChatPaymentException;
use App\Models\ItemType;
use App\Services\InventoryService;
use App\Traits\WeChatPaymentTrait;
use Illuminate\Http\Request;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use Illuminate\Support\Facades\DB;
use App\Models\WechatOrder;
use Exception;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\OperationLogs;
use App\Models\User;

class WeChatPaymentController extends Controller
{
    use WeChatPaymentTrait;

    protected $orderApp;
    protected $notifyUrl;
    protected $orderBodyPrefix = '梦晨网络';

    public function __construct(Request $request)
    {
        $this->orderApp = new Application(config('wechat'));
        $this->notifyUrl = config('wechat.notify_url');

        parent::__construct($request);
    }

    public function createOrder(Request $request)
    {
        $data = $this->validateCreateOrderRequest($request);

        //创建内部订单
        $order = $this->initializeOrder($data, $request);

        //发起预支付请求
        try {
            $result = $this->preparePayment($order);
        } catch (Exception $exception) {
            //更新订单状态，将异常重新抛出
            $this->orderPreparationFailed($order, $exception->getMessage());
        }

        //用户id存0，如果是app订单那么获取不到用户id的
        OperationLogs::add(0, $request->path(), $request->method(),
            '创建微信支付订单', $request->header('User-Agent'));

        //如果支付类型为扫码支付，那么额外返回二维码图片的base64编码字符串
        if ($request->trade_type === 'NATIVE') {
            //获取二维码地址的pr参数的值
            $codeUrlPrValue = $this->getQrCodePrValue($result->code_url);

            $temp = [
                'message' => '订单创建成功',
                //'prepay_id' => $result->prepay_id,
                'code_url_base64' => base64_encode($result->code_url),     //防止游戏端lua解析json失败
                'code_url_pr_value' => $codeUrlPrValue,
                'qr_code' => $this->generateQrCodeStr($result->code_url),
            ];

            return json_encode($temp, JSON_UNESCAPED_UNICODE);
        }

        if ($request->trade_type === 'APP') {
            return [
                'message' => '订单创建成功',
                'app_config' => $this->orderApp->payment->configForAppPayment($result->prepay_id),
            ];
        }

        return [
            'message' => '订单创建成功',
            'prepay_id' => $result->prepay_id,
        ];
    }

    public function getQrCodePrValue($codeUrl)
    {
        $match = [];
        if (! preg_match('/pr=(.*)$/', $codeUrl, $match)) {
            throw new CustomException('获取pr参数的value失败');
        }
        return $match[1];
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
            'trade_type' => 'required|string|in:' . implode(',', $this->tradeTypes),
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
        $data['body'] = $this->orderBodyPrefix . '-' . $item->name . '充值';
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
                $errMsg = $result->err_code . '-' . $result->err_code_des;
                $this->orderPreparationFailed($order, $errMsg);
            }
        }

        if ($result->return_code === 'FAIL') {
            $this->orderPreparationFailed($order, $result->return_msg);
        }

        $this->orderPreparationFailed($order, '发送预支付请求失败');
    }

    //更改订单状态为预支付失败
    protected function orderPreparationFailed($order, $msg)
    {
        $order->order_status = 3;
        $order->order_err_msg = $msg;
        $order->save();

        throw new WeChatPaymentException($msg);
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

//    protected function checkUnfinishedOrder($creatorType, $creatorId)
//    {
//        $orders = WechatOrder::where('order_creator_type', $creatorType)
//            ->where('order_creator_id', $creatorId)
//            ->whereIn('order_status', [1, 2])   //订单状态为已创建或未支付状态的订单
//            ->get();
//        if (!$orders->isEmpty()) {
//            throw new WeChatPaymentException('存在未完成的订单，请支付或取消之前的订单之后再次尝试');
//        }
//        return true;
//    }

    //创建订单号（内部订单号，非微信返回的交易id号）
    protected function createOutTradeNumber()
    {
        //随机数加上当前时间戳，生成md5的订单号
        return md5(mt_rand() . time());
    }

    //微信支付结果通知回调函数
    public function getNotification(Request $request)
    {
        OperationLogs::add(0, $request->path(), $request->method(),
            '微信支付订单回调接口', $request->header('User-Agent'), $request->getContent());

        $response = $this->orderApp->payment->handleNotify(function ($notify, $successful) {
            $order = WechatOrder::where('out_trade_no', $notify->out_trade_no)->first();

            if (empty($order)) {
                return 'Order not exist.';
            }

            if ($order->isPaid()) {
                return true;
            }

            if ($successful) {
                $this->orderPaymentSucceed($order, $notify);
            } else {
                $this->orderPaymentFailed($order, $notify);
            }

            return true;
        });

        return $response;
    }

    protected function orderPaymentSucceed(WechatOrder $order, $notify)
    {
        $order->order_status = 4;
        $order->openid = $notify->openid;
        $order->transaction_id = $notify->transaction_id;
        $order->paid_at = $notify->time_end;
        $order->save();

        $this->deliveryItem($order);    //发货
    }

    protected function orderPaymentFailed(WechatOrder $order, $notify)
    {
        $errMsg = $errMsg = $notify->err_code . '-' . $notify->err_code_des;
        $order->order_status = 5;
        $order->order_err_msg = $errMsg;
        $order->save();
    }

    protected function deliveryItem(WechatOrder $order)
    {
        DB::transaction(function () use ($order) {
            //充值
            $recipientType = $this->orderCreatorTypeMap[$order->order_creator_type]; //玩家 or 代理商角色
            InventoryService::addStock($recipientType, $order->order_creator_id,
                $order->item_type_id, $order->item_amount);

            //更新发货状态
            $order->item_delivery_status = 1;
            $order->save();
        });
    }

    //获取订单数据
    public function getOrder(Request $request, $orderId = null)
    {
        OperationLogs::add(0, $request->path(), $request->method(),
            '查看微信支付订单', $request->header('User-Agent'));

        if (empty($orderId)) {
            $filter = $request->input('filter');
            return WechatOrder::when($filter, function ($query) use ($filter) {
                    $user = User::where('account', $filter)->first();
                    if (empty($user)) {
                        return $query->where('order_creator_id', $filter);
                    }
                    return $query->where('order_creator_id', $user->id);
                })
                ->orderBy($this->order[0], $this->order[1])
                ->paginate($this->per_page);
        }
        return WechatOrder::where('id', $orderId)->firstOrFail()->append('order_qr_code');
    }

    //查询订单状态
    public function checkOrderStatus(Request $request, $outTradeNo)
    {
        OperationLogs::add(0, $request->path(), $request->method(),
            '查看微信支付订单状态', $request->header('User-Agent'));

        $order = WechatOrder::where('out_trade_no', $outTradeNo)->firstOrFail();
        return [
            'status_code' => $order->order_status,
            'status_des' => $this->orderStatusMap[$order->order_status],
        ];
    }

    //关单接口
    public function closeOrder(Request $request, WechatOrder $order)
    {
        OperationLogs::add(0, $request->path(), $request->method(),
            '关闭微信支付订单', $request->header('User-Agent'));

        if (in_array($order->order_status, [2, 5])) {   //预支付订单成功与支付失败的订单
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
                $this->orderCancellationSucceed($order);
                return true;
            }

            if ($result->result_code === 'FAIL') {
                $errMsg = $result->err_code . '-' . $result->err_code_des;
                $this->orderCancellationFailed($order, $errMsg);
            }
        }

        if ($result->return_code === 'FAIL') {
            $this->orderCancellationFailed($order, $result->return_msg);
        }

        $this->orderCancellationFailed($order, '发送取消订单请求失败');
    }

    protected function orderCancellationSucceed($order)
    {
        $order->order_status = 6;
        $order->save();
    }

    protected function orderCancellationFailed($order, $msg)
    {
        $order->order_status = 7;
        $order->order_err_msg = $msg;
        $order->save();

        throw new WeChatPaymentException($msg);
    }

    public function getItemPrice(Request $request)
    {
        OperationLogs::add(0, $request->path(), $request->method(),
            '获取道具价格', $request->header('User-Agent'));

        return ItemType::all();
    }
}
