<?php

use app\modules\api\clients\fns\ClientsFn;

use function app\core\Foundation\feature_group;

return [
    'clients.show' => [ClientsFn::class, 'show']
];
