<?php

namespace app\modules\files\import\controllers;

use app\modules\files\import\fns\ImportFn;
use support\Request;

class ImportController
{

    public function import(Request $request, ImportFn $importFn)
    {
        $path = $request->input('path');
        $jwtUserId = $request->jwtUserId;
        $preset = $request->input('preset');
        $tags_id = $request->input('tags_id');
        $importFn->import($jwtUserId, $path, $preset, $tags_id);
        return success();
    }
}
