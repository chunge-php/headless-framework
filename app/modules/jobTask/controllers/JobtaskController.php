<?php

namespace app\modules\jobTask\controllers;

use app\modules\jobTask\fns\JobtaskFn;
use support\Request;

class JobtaskController
{

    /**
     * Summary of index
     * @param \support\Request $request
     * @return \support\Response
     */
    public function index(Request $request, JobtaskFn $JobtaskFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $JobtaskFn->index($all, $request->limit, $request->offset);
        return success($list);
    }

    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request, JobtaskFn $JobtaskFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $id = $request->input('id') ?? 0;
        unset($all['id']);
        $sensitive =  feature('myclass.SensitiveFilter.detect', $all['name'] ?? '');
        $sensitive2 =  feature('myclass.SensitiveFilter.detect', $all['content'] ?? '');
        if(!empty($all['meta_json']['subject'])){
            $sensitive = array_merge($sensitive, feature('myclass.SensitiveFilter.detect', $all['meta_json']['subject']));
        }
        $sensitive = array_merge($sensitive, $sensitive2);
        if (!empty($sensitive)) {
            return error('exist_sensitive', exist_sensitive, $sensitive);
        }

        if ($id > 0) {
            $JobtaskFn->update($id, $all);
        } else {
            $JobtaskFn->create($all);
        }
        return success();
    }
    public function show(Request $request, JobtaskFn $JobtaskFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($JobtaskFn->show($all));
    }
    public function delete(Request $request, JobtaskFn $JobtaskFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($JobtaskFn->delete($all));
    }
    public function setStatus(Request $request, JobtaskFn $JobtaskFn)
    {
        $status = $request->input('status');
        $id = $request->input('id');
        $uid = $request->jwtUserId;
        return success($JobtaskFn->setStatus($id, $status, $uid));
    }
    public function getKind(Request $request, JobtaskFn $JobtaskFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($JobtaskFn->getKind($all));
    }
    public function futureShow(Request $request, JobtaskFn $JobtaskFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $JobtaskFn->futureShow($all);
        return success($list);
    }
}
