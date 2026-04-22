<?php

namespace app\modules\user\addressbook\controllers;

use app\modules\user\addressbook\fns\AddressbookFn;
use support\Request;

class AddressbookController
{


    /**
     * Summary of index
     * @param \support\Request $request
     * @param \app\modules\user\addressbook\fns\AddressbookFn $AddressbookFn
     * @return \support\Response
     */
    public function index(Request $request, AddressbookFn $AddressbookFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $AddressbookFn->index($all, $request->limit, $request->offset);
        return success($list);
    }

    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request, AddressbookFn $AddressbookFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $id = $request->input('id') ?? 0;
        unset($all['id']);
        $sensitive =  feature('myclass.SensitiveFilter.detect', $all['last_name'] ?? '');
        $sensitive2 =  feature('myclass.SensitiveFilter.detect', $all['first_name'] ?? '');
        $sensitive3 =  feature('myclass.SensitiveFilter.detect', $all['phone'] ?? '');
        $sensitive = array_merge($sensitive, $sensitive2, $sensitive3);
        if (!empty($sensitive)) {
            return error('exist_sensitive', exist_sensitive, $sensitive);
        }
        $is = isUsMobile($all['phone']);
        if (!$is) {
            return error('phone_format_error', phone_format_error, [$all['phone']]);
        }
        if ($id > 0) {
            $AddressbookFn->update($id, $all);
        } else {
            $AddressbookFn->create($all);
        }
        return success();
    }
    public function show(Request $request, AddressbookFn $AddressbookFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($AddressbookFn->show($all));
    }
    public function delete(Request $request, AddressbookFn $AddressbookFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($AddressbookFn->delete($all));
    }

    public function  tagMerge(Request $request, AddressbookFn $AddressbookFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($AddressbookFn->tagMerge($all));
    }
}
