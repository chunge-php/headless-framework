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

use support\Request;

return [
    'debug' => true,
    'debugs' => (bool)getenv('DEBUGS'),
    'error_reporting' => E_ALL,
    'default_timezone' => 'America/New_York',
    'request_class' => Request::class,
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
    'jwt_secret' => env('JWT_SECRET', 'kBRT6Cz+5kGf9Eo58q9q3iJvTzWy21a1Xo4kLp5n7YQ='),
    'app_url' => env('APP_URL', 'localhost'),
    'TWILIO_SID' => getenv('TWILIO_SID'),
    'TWILIO_TOKEN' => getenv('TWILIO_TOKEN'),
    'TWILIO_ServiceSid' => getenv('TWILIO_ServiceSid'),
    'TWILIO_NUMBER' => getenv('TWILIO_NUMBER'),
    'TWILIO_NUMBER2' => getenv('TWILIO_NUMBER2'),
    'logo_bas64' => getenv('logo_bas64'),
    'domain_name_url' => getenv('domain_name_url'),
    'fiserv_merchid' => getenv('fiserv_merchid'),
    'fiserv_pwd' => getenv('fiserv_pwd'),
    'fiserv_user_name' => getenv('fiserv_user_name'),
    'add_funds_url' => getenv('add_funds_url'),
    'sms_quick_uid'=>getenv('sms_quick_uid'),
    'game_url'=>getenv('game_url')

];
