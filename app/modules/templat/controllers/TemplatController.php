<?php
namespace app\modules\templat\controllers;

use app\modules\templat\fns\TemplatFn;
use support\Request;

class TemplatController
{

 /**
     * Summary of index
     * @param \support\Request $request
     * @return \support\Response
     */
    public function index(Request $request,TemplatFn $TemplatFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $TemplatFn->index($all, $request->limit, $request->offset);
        return success($list);
    }

    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request,TemplatFn $TemplatFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $id = $request->input('id') ?? 0;
        unset($all['id']);
        $sensitive =  feature('myclass.SensitiveFilter.detect', $all['name'] ?? '');
        $sensitive2 =  feature('myclass.SensitiveFilter.detect', $all['title'] ?? '');
        $sensitive3 =  feature('myclass.SensitiveFilter.detect', $all['content'] ?? '');
        $sensitive = array_merge($sensitive, $sensitive2,$sensitive3);
        if (!empty($sensitive)) {
            return error('exist_sensitive', exist_sensitive, $sensitive);
        }
        if ($id > 0) {
            $TemplatFn->update($id, $all);
        } else {
            $TemplatFn->create($all);
        }
        return success();
    }
    public function show(Request $request,TemplatFn $TemplatFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($TemplatFn->show($all));
    }
    public function delete(Request $request,TemplatFn $TemplatFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($TemplatFn->delete($all));
    }
    public function getNameIndex(Request $request,TemplatFn $TemplatFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $all['status'] = state_one;
        $list = $TemplatFn->getNameIndex($all, $request->limit, $request->offset);
        return success($list);
    }
}