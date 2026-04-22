<?php

namespace app\queue\redis\sms;

use app\modules\myclass\SendOneBase;
use Webman\RedisQueue\Consumer;

class SendOneTask implements Consumer
{
    public $queue = 'sms-send-one';
    public $connection = 'default'; // 连接名，对应 config/redis_queue.php 文件中的连接
    private $datas;
    public function consume($data)
    {
        $this->datas = $data;
        (new SendOneBase())->start($data);
    }

    /**
     * 处理消费失败
     *
     * @param \Throwable $e
     * @param $package
     */
    public function onConsumeFailure(\Throwable $e, $package)
    {
        debugMessage([$e->getLine()], '短信单条消息队列发送失败第N行报错：' . $e->getLine());
        debugMessage([$e->getFile(), $this->datas, $e->getMessage(), $package], '短信单条消息队列发送失败：');
    }
}
