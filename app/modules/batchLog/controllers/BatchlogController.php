<?php
namespace app\modules\batchLog\controllers;

use app\modules\batchLog\fns\BatchlogFn;
use support\Request;

class BatchlogController
{

 /**
     * Summary of index
     * @param \support\Request $request
     * @return \support\Response
     */
    public function index(Request $request,BatchlogFn $BatchlogFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $BatchlogFn->index($all, $request->limit, $request->offset);
        return success($list);
    }

    public function show(Request $request,BatchlogFn $BatchlogFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($BatchlogFn->show($all));
    }
    public function delete(Request $request,BatchlogFn $BatchlogFn)
    {
        $all = $request->all();
        $all['uid'] = $request->jwtUserId;
        return success($BatchlogFn->delete($all));
    }
}