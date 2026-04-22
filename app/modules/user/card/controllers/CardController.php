<?php

namespace app\modules\user\card\controllers;

use app\modules\user\card\fns\CardFn;
use support\Request;

class CardController
{

    /**
     * Summary of index
     * @param \support\Request $request
     * @return \support\Response
     */
    public function index(Request $request, CardFn $CardFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $CardFn->index($all, $request->limit, $request->offset);
        return success($list);
    }

    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request)
    {
        $all = $request->all();
        $all['uid'] = $request->jwtUserId;
        $id = $request->input('id') ?? 0;
        if ($id > 0) {
            $res =  feature('myclass.CardPointeGateway.updateProfile', $all['uid'], $all);
        } else {
            $res =  feature('myclass.CardPointeGateway.createProfile', $all['uid'], $all);
        }
        return success($res);
    }
    public function show(Request $request, CardFn $CardFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($CardFn->show($all));
    }
    public function delete(Request $request, CardFn $CardFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($CardFn->delete($all));
    }
    public function defaultState(Request $request, CardFn $CardFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($CardFn->defaultState($all));
    }
    public function setState(Request $request, CardFn $CardFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($CardFn->setState($all));
    }
}
