<?php

namespace app\modules\game\controllers;

use app\modules\game\fns\GameFn;
use support\Request;

class GameController
{

    /**
     * Summary of index
     * @param \support\Request $request
     * @return \support\Response
     */
    public function index(Request $request, GameFn $GameFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $GameFn->index($all, $request->limit, $request->offset);
        return success($list);
    }

    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request, GameFn $GameFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $id = $request->input('id') ?? 0;
        unset($all['id']);
        if ($id > 0) {
            $GameFn->update($id, $all);
        } else {
            $id =  $GameFn->create($all);
        }
        return success($id);
    }
    public function show(Request $request, GameFn $GameFn)
    {

        $id = $request->input('id');
        return success($GameFn->show($id));
    }
    public function delete(Request $request, GameFn $GameFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($GameFn->delete($all));
    }
    public function setMy(Request $request, GameFn $GameFn)
    {
        $all = $request->all();
        $all['uid'] = $request->jwtUserId;
        return success($GameFn->setMy($all));
    }
    public function getMy(Request $request, GameFn $GameFn)
    {
        $uid = $request->jwtUserId;
        return success($GameFn->getMy($uid));
    }
    public function sendSmsResult(Request $request, GameFn $GameFn)
    {
        $encrypted = $request->input('encrypted') ?? '';
        $iv = $request->input('iv') ?? '';
        $info = AESDeCode($encrypted, $iv);
        $to = $info['to'] ?? '';
        $uid = $info['uid'] ?? '';
        $body = $info['body'] ?? '';
        if (empty($to) || empty($uid) || empty($body)) {
            return error('params_error');
        }
        return success($GameFn->sendSmsResult($to, $uid, $body));
    }
    public function apiShow(Request $request, GameFn $GameFn)
    {
        $code = $request->input('code');
        return success($GameFn->apiShow($code));
    }
    public function sendCode(Request $request, GameFn $GameFn)
    {
        $encrypted = $request->input('encrypted') ?? '';
        $iv = $request->input('iv') ?? '';
        $info = AESDeCode($encrypted, $iv);
        $to = $info['to'] ?? '';
        $uid = $info['uid'] ?? '';
        if (empty($to) || empty($uid)) {
            return error('params_error');
        }
        $jobid = $GameFn->sendCode($to, $uid);
        return success($jobid);
    }
    public function verCode(Request $request, GameFn $GameFn)
    {
        $encrypted = $request->input('encrypted') ?? '';
        $iv = $request->input('iv') ?? '';
        $info = AESDeCode($encrypted, $iv);
        $code = $info['code'] ?? '';
        $to = $info['to'] ?? '';
        if (empty($code) || empty($to)) {
            return error('params_error');
        }
        $res = $GameFn->verCode($code, $to);
        return success($res);
    }
    public function getJobResult(Request $request, GameFn $GameFn)
    {
        $jobId = $request->input('jobid') ?? '';
        if (empty($jobId)) {
            return error('params_error');
        }
        $res = $GameFn->getJobResult($jobId);
        return success($res);
    }
    public function getIndexLog(Request $request, GameFn $GameFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $GameFn->getIndexLog($all, $request->limit, $request->offset);
        return success($list);
    }
    public function getParticipantIndex(Request $request, GameFn $GameFn)
    {
        $all = $request->all();
        $all['uid'] = $request->jwtUserId;
        $list = $GameFn->getParticipantIndex($all, $request->limit, $request->offset);
        return success($list);
    }
}
