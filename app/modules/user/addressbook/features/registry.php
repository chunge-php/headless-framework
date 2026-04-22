<?php

use app\modules\user\addressbook\fns\AddressbookFn;

use function app\core\Foundation\feature_group;

return [
    'user.addressbook.getDateUser' => [AddressbookFn::class, 'getDateUser']
];
