<?php

namespace app\queue\redis\sms;


use Webman\RedisQueue\Consumer;
use  app\modules\myclass\SendBase;

class SendSmsTask implements Consumer
{
    public $queue = 'sms-send-sms';
    public $connection = 'default'; // 连接名，对应 config/redis_queue.php 文件中的连接
    private $datas;
    public function consume($data)
    {
        $this->datas = $data;
        (new SendBase())->start($data);
    }

    /**
     * 处理消费失败
     *
     * @param \Throwable $e
     * @param $package
     */
    public function onConsumeFailure(\Throwable $e, $package)
    {
        debugMessage([$e->getLine(),$e->getFile(), $this->datas, $e->getMessage(),$package], '短信消费队列失败：');
    }
}
