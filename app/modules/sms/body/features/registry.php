<?php

use app\modules\sms\body\fns\BodyFn;

use function app\core\Foundation\feature_group;

return [
    'sms.body.create' => [BodyFn::class, 'create'],
    'sms.body.delete' => [BodyFn::class, 'delete'],
];
