<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Exceptions\WeChatPaymentException;
use App\Models\ItemType;
use App\Models\User;
use App\Models\WechatOrderMap;
use Illuminate\Http\Request;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use Illuminate\Support\Facades\Log;
use App\Models\WechatOrder;
use Exception;

class WeChatPaymentController extends Controller
{
    use WechatOrderMap;

    protected $orderApp;
    protected $notifyUrl;

    public function __construct(Request $request)
    {
        $this->orderApp = new Application(config('wechat'));
        //$this->notifyUrl = env('APP_URL') . '/api/wechat/order/notification';
        $this->notifyUrl = 'http://admin-new.11majiang.com/api/wechat/order/notification';
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
        return $result;

        //TODO 返回前端支付二维码或者prepareid
        if ($result->return_code) {
            return true;    //返回预支付会话id等信息
        } else {
            return false;   //返回错误信息
        }
    }

    protected function validateCreateOrderRequest(Request $request)
    {
        $this->validate($request, [
            'order_creator_type' => 'required|integer|in:1,2',
            'order_creator_id' => 'required|integer',
            'item_type_id' => 'required|exists:item_type,id',
            'item_amount' => 'required|integer|min:1',
            'trade_type' => 'required|string|in:' . implode(',', array_keys($this->tradeTypeMap))
        ]);
        return $request->intersect([
            'order_creator_type', 'order_creator_id', 'item_type_id', 'item_amount', 'trade_type'
        ]);
    }

    protected function initializeOrder($data, $request)
    {
        $this->checkUnfinishedOrder($data['order_creator_type'], $data['order_creator_id']);

        $item = ItemType::find($data['item_type_id']);
        $data['out_trade_no'] = $this->createOutTradeNumber();
        $data['trade_type'] = $this->tradeTypeMap[$data['trade_type']];
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

    //获取微信支付结果通知
    public function getNotification(Request $request)
    {
        Log::info('wechat', $request->toArray());
        return [
            'return_code' => 'SUCCESS',
        ];
    }

    //查询订单状态
    public function checkOrder(Request $request)
    {

    }
}
