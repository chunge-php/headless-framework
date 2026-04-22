<?php

namespace app\modules\test\controllers;

use app\modules\myclass\ApiAuth;
use app\modules\test\fns\TestFn;
use support\Request;

class TestController
{

    /**
     * Summary of index
     * @param \support\Request $request
     * @return \support\Response
     */
    public function index(Request $request, TestFn $TestFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $TestFn->index($all, $request->limit, $request->offset);
        return success($list);
    }
    public function getSignData(Request $request, ApiAuth $ApiAuth)
    {
        $access_key = $request->input('access_key') ?? '';
        $secret_key  = $request->input('secret_key') ?? '';
        $params = $request->all();
        unset($params['access_key'], $params['secret_key']);
        // Step2 AES 加密
        $dataEncrypted = ApiAuth::aesEncrypt($params, $secret_key);
        // Step3 构建参数
        $params = ApiAuth::buildParams($access_key);
        $params['data'] = $dataEncrypted;// 加密后的内容
        // Step4 计算 HMAC-SHA256
        $params['sign'] = ApiAuth::calcSign($params, $secret_key);
        return success($params);
    }
    /**
     * 加密
     * @param \support\Request $request
     * @return \support\Response
     */
    public function getAesEncode(Request $request)
    {
        try {
            $all = $request->all();
            $data = AESEnCode(json_encode($all, JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_SLASHES));
            return  success($data);
        } catch (\Exception $e) {
            return error($e->getMessage(), $e->getCode());
        }
    }
    /**
     * 解密
     * @param \support\Request $request
     * @return \support\Response
     */
    public function getAesDeCode(Request $request)
    {
        try {

            $encrypted = $request->input('encrypted') ?? '';
            $iv = $request->input('iv') ?? '';
            $data = AESDeCode($encrypted, $iv);
            return  success($data);
        } catch (\Exception $e) {
            return error($e->getMessage(), $e->getCode(), [$request->all(), $e->getLine(), $e->getFile()]);
        }
    }
    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request, TestFn $TestFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $id = $request->input('id') ?? 0;
        unset($all['id']);
        if ($id > 0) {
            $TestFn->update($id, $all);
        } else {
            $TestFn->create($all);
        }
        return success();
    }
    public function show(Request $request, TestFn $TestFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($TestFn->show($all));
    }
    public function delete(Request $request, TestFn $TestFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($TestFn->delete($all));
    }
}
