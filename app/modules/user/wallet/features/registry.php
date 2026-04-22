<?php

use app\modules\user\wallet\fns\UserWalletFn;
use app\modules\user\wallet\fns\BillFn;

return [
    'user.wallet.create' => [UserWalletFn::class, 'create'],
    'user.wallet.getBalance' => [UserWalletFn::class, 'getBalance'],
    'user.wallet.nextBillNo'=>[BillFn::class, 'nextBillNo'],
    'user.wallet.autoTopupStatus'=>[UserWalletFn::class, 'autoTopupStatus'],
];
