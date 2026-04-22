<?php

namespace app\queue\redis\email;

use  app\modules\myclass\SendBase;

use Webman\RedisQueue\Consumer;

class SendEmailTask implements Consumer
{
    public $queue = 'email-send-email';
    public $connection = 'default'; // 连接名，对应 config/redis_queue.php 文件中的连接

    public function consume($data)
    {
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
        colorText($e->getLine(), 'red');
        colorText("邮件消费队列失败：" . $e->getMessage() . '(' . getMillisecondTime() . ')', 'red');
        debugMessage("邮件消费队列失败：" . $e->getMessage());
    }
}
