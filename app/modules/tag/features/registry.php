<?php

use app\modules\tag\fns\TagFn;

use function app\core\Foundation\feature_group;
return [
    'tag.mapByTargets'=>[TagFn::class,'mapByTargets'],
    'tag.createTagBook'=>[TagFn::class,'createTagBook'],
    'tag.tagMerge'=>[TagFn::class,'tagMerge'],
    'tag.createTagBookBatch'=>[TagFn::class,'createTagBookBatch'],
    'tag.getIdArr'=>[TagFn::class,'getIdArr'],
    'tag.deleteTagBook'=>[TagFn::class,'deleteTagBook'],
];

