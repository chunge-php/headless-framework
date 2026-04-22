<?php

namespace app\modules\tag\controllers;

use app\modules\tag\fns\TagFn;
use support\Request;

class TagController
{

    /**
     * Summary of index
     * @param \support\Request $request
     * @return \support\Response
     */
    public function index(Request $request, TagFn $TagFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $TagFn->index($all, $request->limit, $request->offset);
        return success($list);
    }

    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request, TagFn $TagFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $id = $request->input('id') ?? 0;
        unset($all['id']);
        $sensitive =  feature('myclass.SensitiveFilter.detect', $all['name'] ?? '');
        $sensitive2 =  feature('myclass.SensitiveFilter.detect', $all['description'] ?? '');
        $sensitive = array_merge($sensitive, $sensitive2);
        if (!empty($sensitive)) {
            return error('exist_sensitive', exist_sensitive, $sensitive);
        }
        if ($id > 0) {
            $TagFn->update($id, $all);
        } else {
            $TagFn->create($all);
        }
        return success();
    }
    public function show(Request $request, TagFn $TagFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($TagFn->show($all));
    }
    public function delete(Request $request, TagFn $TagFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($TagFn->delete($all));
    }
    public function getNameIndex(Request $request, TagFn $TagFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $TagFn->getNameIndex($all, $request->limit, $request->offset);
        return success($list);
    }
}
