<?php

namespace app\modules\configItem\controllers;

use app\modules\configItem\fns\ConfigItemFn;
use support\Request;

class ConfigItemController
{

 
    public function index(Request $request,ConfigItemFn $ConfigItemFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $ConfigItemFn->index($all, $request->limit, $request->offset);
        return success($list);
    }

    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request, ConfigItemFn $ConfigItemFn)
    {
        $all = $request->all();
        $ConfigItemFn->create($all);
        return success();
    }
    public function show(Request $request, ConfigItemFn $ConfigItemFn)
    {
        $all = $request->all();
        return success($ConfigItemFn->show($all));
    }
    public function delete(Request $request, ConfigItemFn $ConfigItemFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($ConfigItemFn->delete($all));
    }
    public function getName(Request $request, ConfigItemFn $ConfigItemFn)
    {
        $signs = $request->input('signs') ?? '';
        return success($ConfigItemFn->getName($signs));
    }
}
