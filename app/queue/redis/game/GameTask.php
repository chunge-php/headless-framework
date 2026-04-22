<?php

namespace app\queue\redis\game;


use Webman\RedisQueue\Consumer;
use  app\modules\myclass\SendBase;
use support\Db;

class GameTask implements Consumer
{
    public $queue = 'game-task';
    public $connection = 'default'; // 连接名，对应 config/redis_queue.php 文件中的连接
    private $datas;
    public function consume($data)
    {
        $this->startFn($data);
    }
    public function getGroupUsersId($uid)
    {

        $id =  Db::table('group_users')->where('uid', $uid)->where('channel_type', state_three)->orderBy('id', 'desc')->first();
        if (!$id) {
            $id =  Db::table('group_users')->insertGetId([
                'name' => 'Game',
                'uid' => $uid,
                'total' => 0,
                'channel_type' => state_three,
                'description' => 'This data is derived from the mobile phone numbers collected during the game.'

            ]);
            $file = public_path('uploads/' . $id . '/Game.csv');
        } else {
            if ($id->total >= 10000) {
                $sort = Db::table('group_users')->where('uid', $uid)->where('channel_type', state_three)->count();
                $sort++;
                $id =  Db::table('group_users')->insertGetId([
                    'name' => 'Game' . '(' . $sort . ')',
                    'uid' => $uid,
                    'total' => 0,
                    'channel_type' => state_three,
                    'description' => 'This data is derived from the mobile phone numbers collected during the game.'
                ]);
                $file = public_path('uploads/' . $id . '/Game.csv');
            } else {
                $id = $id->id;
                $file = $id->file_url ?? public_path('uploads/' . $id . '/Game.csv');
            }
        }
        return ['id' => $id, 'file' => $file];
    }
    public function startFn($data)
    {
        $phone = phone_to_digits($data['to']);
        if (!$phone) {
            return true;
        }
        $getGroupUsersId = $this->getGroupUsersId($data['uid']);
        $file_url = $this->extractPathAfterPublic($getGroupUsersId['file']);
        $file = public_path($file_url);
        // 如果文件不存在，创建并写入表头（可选）
        // 1. 确保目录存在
        $dir = dirname($file);
        if (!is_dir($dir)) {
            // 递归创建目录，并简单做一下错误检查
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                // 这里你可以换成自己的日志函数
                debugMessage("无法创建目录：{$dir}");
                return false;
            }
        }

        // 2. 确保文件存在（如需写表头）
        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            if ($fp === false) {
                debugMessage("无法创建文件：{$file}");
                return false;
            }
            fputcsv($fp, ['', '', 'phone']);
            fclose($fp);
        }
        $total = 0;
        $fp = fopen($file, 'a');
        // 只写手机号（假设字段为 to）
        $total++;
        fputcsv($fp, ['', '', $phone]);
        fclose($fp);
        if ($total > 0) {
            Db::table('group_users')->where('id', $getGroupUsersId['id'])->increment('total', $total, ['file_url' => $file_url]);
        }
    }
    public function extractPathAfterPublic($fullPath)
    {
        $pos = strpos($fullPath, 'public');
        if ($pos === false) {
            return $fullPath;
        }

        // 去掉 'public' 之前的所有内容，保留之后的完整路径
        return substr($fullPath, $pos + 6); // 6 = strlen('public')
    }
    /**
     * 处理消费失败
     *
     * @param \Throwable $e
     * @param $package
     */
    public function onConsumeFailure(\Throwable $e, $package)
    {
        debugMessage([$e->getLine(), $e->getFile(), $this->datas, $e->getMessage(), $package], '游戏收集失败');
    }
}
