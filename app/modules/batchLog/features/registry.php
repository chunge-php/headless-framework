<?php

use app\modules\batchLog\fns\BatchlogFn;

use function app\core\Foundation\feature_group;
return [
    'batchLog.create'=>[BatchlogFn::class,'create'],
    'batchLog.update'=>[BatchlogFn::class,'update'],
    'batchLog.delete'=>[BatchlogFn::class,'delete'],
];

