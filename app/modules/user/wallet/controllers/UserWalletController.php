<?php

namespace app\modules\user\wallet\controllers;

use app\modules\user\wallet\fns\UserWalletFn;
use support\Request;
use support\Response;

class UserWalletController
{
    public function test(): Response
    {
        return success();
    }
    public function getBalance(Request $request, UserWalletFn $userWalletFn)
    {
        $info  = $userWalletFn->getBalance($request->jwtUserId);
        if(!empty($info['balance'])){
            $info['balance'] =money_to_decimal($info['balance']);
        }
        return success($info);
    }
    public function setAutoTopup(Request $request, UserWalletFn $userWalletFn)
    {
        $all = $request->all();
        $all['uid'] = $request->jwtUserId;
        $info  = $userWalletFn->setAutoTopup($all);
        return success($info);
    }
    public function getAutoTopup(Request $request, UserWalletFn $userWalletFn)
    {
        $info  = $userWalletFn->getAutoTopup($request->jwtUserId);
        return success($info);
    }
    public function autoTopupIndex(Request $request, UserWalletFn $userWalletFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $userWalletFn->autoTopupIndex($all, $request->limit, $request->offset);
        return success($list);
    }
    public function setReminderPrice(Request $request, UserWalletFn $userWalletFn)
    {
        $reminder_price = $request->input('reminder_price')??0;
        $info  = $userWalletFn->setReminderPrice($request->jwtUserId,$reminder_price);
        return success($info);
    }

    public function recharge(Request $request, UserWalletFn $UserWalletFn){
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($UserWalletFn->recharge($all));

    }
}
