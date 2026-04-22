<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\Log;
use support\Request;
use app\process\Http;

global $argv;

return [

    // File update detection and automatic reload
    'monitor' => [
        'handler' => app\process\Monitor::class,
        'reloadable' => false,
        'constructor' => [
            // Monitor these directories
            'monitorDir' => array_merge([
                config_path(),
                app_path(),
                base_path() . '/process',
                base_path() . '/support',
                base_path() . '/resource',
                base_path() . '/.env',
                base_path() . '/queue',
            ], glob(base_path() . '/plugin/*/app'), glob(base_path() . '/plugin/*/config'), glob(base_path() . '/plugin/*/api')),
            // Files with these suffixes will be monitored
            'monitorExtensions' => [
                'php',
                'html',
                'htm',
                'env'
            ],
            'options' => [
                'enable_file_monitor' => !in_array('-d', $argv) && DIRECTORY_SEPARATOR === '/',
                'enable_memory_monitor' => DIRECTORY_SEPARATOR === '/',
            ]
        ]
    ],
    'auto_topup_patrol'  => [
        'handler'  => app\process\AutoTopupPatrol::class
    ],
    'sms_bill_task'  => [
        'handler'  => app\process\SmsBillTask::class
    ],
    'birthday_task' => [
        'handler'  => app\process\BirthdayTask::class
    ],
    'appointment_task' => [
        'handler'  => app\process\AppointmentTask::class
    ],
    'sms_channel'  => [
        'handler'  => app\process\SmsChannel::class
    ],
    'sms_game'  => [
        'handler'  => app\process\SmsGame::class
    ],
    'sms_gather'  => [
        'handler'  => app\process\SmsGather::class
    ],
];
