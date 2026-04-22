<?php

namespace app\modules\files\import\fns;

use Webman\RedisQueue\Redis as RedisQueueRedis;

class ImportFn
{

    public function import($jwtUserId, $file, $preset, $tags_id)
    {
        $created_at = dayDateTime();
        $batch_data = ['name' => '通讯录导入:' . dayDateTime(), 'uid' => (int)$jwtUserId, 'scope' => linkman_str, 'status' => 0, 'total' => 0, 'success' => 0, 'fail' => 0, 'describe' => '', 'created_at' => dayDateTime()];
        $batchs_id = 0;
        try {
            $batchs_id =  feature('batchLog.create', $batch_data);
        } catch (\Exception $e) {
        }
        RedisQueueRedis::send('import-address-book', ['jwtUserId' => $jwtUserId, 'batchs_id' => $batchs_id, 'file' => $file, 'created_at' => $created_at, 'preset' => $preset, 'tags_id' => $tags_id]);
        return $batchs_id;
    }
}
