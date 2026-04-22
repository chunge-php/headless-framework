<?php
namespace app\modules\clutter\legal\controllers;

use app\modules\clutter\legal\fns\LegalFn;
use support\Request;

class LegalController
{

 /**
     * Summary of index
     * @param \support\Request $request
     * @return \support\Response
     */
    public function index(Request $request,LegalFn $LegalFn)
    {
        $all = $request->all();
        $list = $LegalFn->index($all, $request->limit, $request->offset);
        return success($list);
    }

    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request,LegalFn $LegalFn)
    {
        $all = $request->all();
        $id = $request->input('id') ?? 0;
        unset($all['id']);
        if ($id > 0) {
            $LegalFn->update($id, $all);
        } else {
            $LegalFn->create($all);
        }
        return success();
    }
    public function show(Request $request,LegalFn $LegalFn)
    {
        $all = $request->all();
        return success($LegalFn->show($all));
    }
    public function delete(Request $request,LegalFn $LegalFn)
    {
        $all = $request->all();
        return success($LegalFn->delete($all));
    }
    public function slugName(Request $request,LegalFn $LegalFn)
    {
        $slug = $request->input('slug');
        return success($LegalFn->slugName($slug));
    }
}