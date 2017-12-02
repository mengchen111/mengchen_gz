<?php

namespace App\Jobs;

use App\Services\GameServer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\GameNotificationMarquee;
use App\Models\OperationLogs;
use Illuminate\Support\Facades\Log;

class SendGameNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationModel;    //公告模型实例
    protected $formData;        //POST数据
    protected $apiAddress;      //游戏服地址

    public $tries = 3;          //最大重试次数
    public $timeout = 10;       //任务执行的最长时间

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notificationModel, $formData, $apiAddress)
    {
        $this->notificationModel = $notificationModel;
        $this->formData = $formData;
        $this->apiAddress = $apiAddress;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        GameServer::request('POST', $this->apiAddress, $this->formData);

        $this->notificationModel->sync_state = 3;
        $this->notificationModel->failed_description = '';
        $this->notificationModel->save();

        OperationLogs::add(1, $this->apiAddress, 'POST', '后台队列同步公告成功',
            'Guzzle', json_encode($this->formData));
    }

    //如果请求过程中Guzzle抛出异常，则记录在notificationModel模型表中
    public function failed(\Exception $e)
    {
        $this->notificationModel->sync_state = 4;
        $this->notificationModel->failed_description = $e->getMessage();
        $this->notificationModel->save();

        throw $e;   //将异常重新抛出
    }
}
