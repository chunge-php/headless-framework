<?php

use app\modules\sms\register\fns\SmsFn;

return [
    'sms.register.send'=> [SmsFn::class, 'send'],
    'sms.register.verifyCode'=> [SmsFn::class, 'verifyCode'],
];

