<?php

namespace app\modules\sms\body\controllers;

use app\modules\sms\body\fns\BodyFn;
use support\Request;

class BodyController
{

    /**
     * Summary of index
     * @param \support\Request $request
     * @return \support\Response
     */
    public function index(Request $request, BodyFn $BodyFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $BodyFn->index($all, $request->limit, $request->offset);
        return success($list);
    }
    public function monthIndex(Request $request, BodyFn $BodyFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $BodyFn->monthIndex($all, $request->limit, $request->offset);
        return success($list);
    }
    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request, BodyFn $BodyFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $sensitive =  feature('myclass.SensitiveFilter.detect', $all['content'] ?? '');
        $sensitive2 =  feature('myclass.SensitiveFilter.detect', $all['subject'] ?? '');
        $sensitive = array_merge($sensitive, $sensitive2);
        if (!empty($sensitive)) {
            return error('exist_sensitive', exist_sensitive, $sensitive);
        }
        if (!empty($all['account_number']) && isset($all['price_id']) && $all['price_id'] !=3) {
            $res = [];
            foreach ($all['account_number'] as $p) {
                $is = isUsMobile($p);
                if (!$is) {
                    $res[] = $p;
                }
            }
            if(!empty($res)){
                return error('phone_format_error', phone_format_error, $res);
            }
        }
        $info =  $BodyFn->create($all);
        return success($info);
    }
    public function showIndex(Request $request, BodyFn $BodyFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($BodyFn->showIndex($all, $request->limit, $request->offset));
    }
    public function delete(Request $request, BodyFn $BodyFn)
    {
        $all = $request->all();
        $all['uid'] = $request->jwtUserId;
        return success($BodyFn->delete($all));
    }
    public function anewSend(Request $request, BodyFn $BodyFn)
    {
        $all = $request->all();
        $all['uid'] = $request->jwtUserId;
        return success($BodyFn->anewSend($all));
    }
}
