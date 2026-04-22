<?php

use app\modules\sms\config\fns\ConfigFn;

use function app\core\Foundation\feature_group;

return [
    'sms.config.getPriceId' => [ConfigFn::class, 'getPriceId']
];
