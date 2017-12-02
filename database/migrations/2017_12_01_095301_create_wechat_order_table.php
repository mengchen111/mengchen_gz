<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_order', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('order_creator_type')->comment('订单创建者的类型(1-玩家,2-代理商)');
            $table->unsignedInteger('order_creator_id')->comment('订单创建者的id');
            $table->unsignedInteger('item_type_id')->comment('道具类型');
            $table->unsignedInteger('item_amount')->comment('道具充值数量');
            $table->unsignedInteger('item_delivery_status')->default(0)->comment('发货状态(0-未发货,1-已发货)');
            $table->string('out_trade_no', 32)->comment('内部订单号');
            $table->string('trade_type', 16)->comment('交易类型');
            $table->string('body', 128)->comment('商品描述');
            $table->text('detail')->nullable()->comment('商品详情');
            $table->unsignedInteger('total_fee')->comment('订单总金额(分)');
            $table->string('spbill_create_ip', 16)->comment('终端IP');
            $table->unsignedTinyInteger('order_status')->default(1)
                ->comment('订单状态(1-内部订单创建成功,2-预支付订单创建成功,3-预支付订单创建失败,4-支付成功,5-支付失败,6-已关闭)');
            $table->string('order_err_msg')->nullable()->comment('订单创建和支付过程中微信返回的错误消息');
            $table->string('prepay_id', 64)->nullable()->comment('预支付交易会话标识');
            $table->string('code_url', 64)->nullable()->comment('扫码支付时的二维码链接');
            $table->string('openid', 128)->nullable()->comment('微信用户标识');
            $table->string('transaction_id', 32)->nullable()->comment('微信支付订单号');
            $table->timestamp('paid_at')->nullable()->comment('支付完成时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wechat_order');
    }
}
