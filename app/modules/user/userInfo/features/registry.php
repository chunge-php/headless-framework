<?php

use app\modules\user\userInfo\fns\UserinfoFn;
use function app\core\Foundation\feature_group;
return feature_group('user.userInfo.', UserinfoFn::class, [
    'getUserInfo',
    'create',
    'identitiesCreate',
    'identitiesUpdate',
    'credentialCreate',
    'getUserInfoToken',
    'getSecretHash',
]);
