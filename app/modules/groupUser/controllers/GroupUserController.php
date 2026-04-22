<?php
namespace app\modules\groupUser\controllers;

use app\modules\groupUser\fns\GroupUserFn;
use support\Request;

class GroupUserController
{

 /**
     * Summary of index
     * @param \support\Request $request
     * @return \support\Response
     */
    public function index(Request $request,GroupUserFn $GroupuserFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $GroupuserFn->index($all, $request->limit, $request->offset);
        return success($list);
    }

    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request,GroupUserFn $GroupuserFn)
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
            $GroupuserFn->update($id, $all);
        } else {
            $GroupuserFn->create($all);
        }
        return success();
    }
    public function show(Request $request,GroupUserFn $GroupuserFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($GroupuserFn->show($all));
    }
    public function delete(Request $request,GroupUserFn $GroupuserFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($GroupuserFn->delete($all));
    }
    public function  tagMerge(Request $request,GroupUserFn $GroupuserFn)
    {
            $all = $request->all();
            $all['jwtUserId'] = $request->jwtUserId;
            return success($GroupuserFn->tagMerge($all));
    }
}