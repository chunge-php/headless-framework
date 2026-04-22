<?php

namespace app\modules\sms\config\controllers;

use app\modules\sms\config\fns\ConfigFn;
use support\Request;

class ConfigController
{

    public function priceName(Request $request, ConfigFn $configFn)
    {
        return success($configFn->priceName());
    }
    public function index(Request $request, ConfigFn $ConfigFn)
    {
        $all = $request->all();
        $list = $ConfigFn->index($all, $request->limit, $request->offset);
        return success($list);
    }
    public function create(Request $request, ConfigFn $ConfigFn)
    {
        $all = $request->all();
        $id = $request->input('id') ?? 0;
        unset($all['id']);
        if ($id > 0) {
            $ConfigFn->update($id, $all);
        } else {
            $ConfigFn->create($all);
        }
        return success();
    }

    public function show(Request $request, ConfigFn $ConfigFn)
    {
        $all = $request->all();
        return success($ConfigFn->show($all));
    }
    public function delete(Request $request, ConfigFn $ConfigFn)
    {
        $all = $request->all();
        return success($ConfigFn->delete($all));
    }
}
