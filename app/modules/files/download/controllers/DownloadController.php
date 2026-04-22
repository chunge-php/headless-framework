<?php

namespace app\modules\files\download\controllers;

use app\modules\files\download\fns\DownloadFn;
use support\Request;

class DownloadController
{

    public function templatDownload(Request $request)
    {
        $preset = (string) $request->get('preset', '');
        return feature('myclass.CsvTemplate.call', $preset);
    }
    public function customDownload(Request $request)
    {
        $headers = (string) $request->get('headers', '');
        $filename = (string) $request->get('filename', '');
        $title = (string) $request->get('title', '');
        $example = (string) $request->get('example', '');
        $bom = (string) $request->get('bom', '');
        return feature('myclass.CsvTemplate.custom', $headers, $filename, $title, $example, $bom);
    }
    /**
     * Summary of index
     * @param \support\Request $request
     * @return \support\Response
     */
    public function index(Request $request, DownloadFn $DownloadFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $list = $DownloadFn->index($all, $request->limit, $request->offset);
        return success($list);
    }

    /**
     * Summary of create
     * @param \support\Request $request
     * @return \support\Response
     */
    public function create(Request $request, DownloadFn $DownloadFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $id = $request->input('id') ?? 0;
        unset($all['id']);
        if ($id > 0) {
            $DownloadFn->update($id, $all);
        } else {
            $DownloadFn->create($all);
        }
        return success();
    }
    public function show(Request $request, DownloadFn $DownloadFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($DownloadFn->show($all));
    }
    public function delete(Request $request, DownloadFn $DownloadFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($DownloadFn->delete($all));
    }
}
