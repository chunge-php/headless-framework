<?php

namespace app\modules\user\wallet\controllers;

use app\modules\user\wallet\fns\BillFn;
use support\Request;

class BillController
{

    public function index(Request $request, BillFn $billFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $billFn->index($all, $request->limit, $request->offset);
        return success($list);
    }
}
