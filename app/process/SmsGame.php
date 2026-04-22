<?php

namespace app\process;

use support\Db;
use support\Redis;
use Workerman\Timer;

class SmsGame
{
    public function onWorkerStart()
    {
        colorText('游戏分组生成' . dayDateTime(), 'green');
        // 每 60 秒巡逻一次
        Timer::add(60, function () {
            $this->startFn();
        });
    }
    public function getGroupUsersId()
    {

        $id =  Db::table('group_users')->where('uid', config('app.sms_quick_uid'))->where('channel_type', state_three)->orderBy('id', 'desc')->first();
        if (!$id) {
            $id =  Db::table('group_users')->insertGetId([
                'name' => 'Game',
                'uid' => config('app.sms_quick_uid'),
                'total' => 0,
                'channel_type' => state_three,
                'description' => 'This file contains users’ phone numbers collected through in-game behavior tracking. These numbers were obtained when users participated in the game and have been recorded for subsequent marketing and promotional use.'

            ]);
            $file = public_path('uploads/' . $id . '/Game.csv');
        } else {
            if ($id->total >= 10000) {
                $sort = Db::table('group_users')->where('uid', config('app.sms_quick_uid'))->where('channel_type', state_three)->count();
                $sort++;
                $id =  Db::table('group_users')->insertGetId([
                    'name' => 'Game' . '(' . $sort . ')',
                    'uid' => config('app.sms_quick_uid'),
                    'total' => 0,
                    'channel_type' => state_three,
                    'description' => 'This file contains users’ phone numbers collected through in-game behavior tracking. These numbers were obtained when users participated in the game and have been recorded for subsequent marketing and promotional use.'
                ]);
                $file = public_path('uploads/' . $id . '/Game.csv');
            } else {
                $id = $id->id;
                $file = $id->file_url ?? public_path('uploads/' . $id . '/Game.csv');
            }
        }
        return ['id' => $id, 'file' => $file];
    }
    public function startFn()
    {
        $key = 'sms:game';

        $dateTime = Redis::get($key) ?? dayDateTime();
        $getGroupUsersId = $this->getGroupUsersId();

        // 拉启用规则
        $query = Db::table('sms_channel_logs')
            ->select(['sms_channel_logs.*', 'users.first_name', 'users.last_name'])
            ->leftJoin('users', 'sms_channel_logs.uid', '=', 'users.id')
            ->where('sms_channel_logs.created_at', '>=', $dateTime)
            ->where('sms_channel_logs.channel_type', state_three)
            ->orderBy('sms_channel_logs.id');
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
            fputcsv($fp, ['phone']);
            fclose($fp);
        }
        $total = 0;
        $query->chunk(1000, function ($batch) use ($file, &$total) {
            $fp = fopen($file, 'a');

            foreach ($batch as $r) {
                // 只写手机号（假设字段为 to）
                $phone = phone_to_digits($r->to);
                if ($phone) {
                    $total++;
                    fputcsv($fp, [$r->first_name ?? '', $r->last_name ?? '', $phone]);
                }
            }

            fclose($fp);
        });
        Redis::set($key, dayDateTime());
        Db::table('group_users')->where('id', $getGroupUsersId['id'])->increment('total', $total, ['file_url' => $file_url]);
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
}
