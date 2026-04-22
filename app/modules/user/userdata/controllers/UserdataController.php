<?php

namespace app\modules\user\userdata\controllers;

use app\modules\user\userdata\fns\UserdataFn;
use support\Request;

class UserdataController
{
    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request, UserdataFn $UserdataFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $sensitive = [];
        $data=['company_name','last_name','first_name','country','address1','address2','city','state','zip_code'];
        foreach ($data as  $value) {
            if (!empty($all[$value])) {
                $sensitive =  array_merge($sensitive,feature('myclass.SensitiveFilter.detect', $all[$value]));
            }
        }
        if (!empty($sensitive)) {
            return error('exist_sensitive', exist_sensitive, $sensitive);
        }
        $id =  $UserdataFn->create($all);
        return success($id);
    }
    public function show(Request $request, UserdataFn $UserdataFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($UserdataFn->show($all));
    }
}
