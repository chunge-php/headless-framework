<?php

use app\modules\auth\email\fns\EmailFn;

return [
    'auth.email.sendRegister' => [EmailFn::class, 'sendRegister'],
];
