<?php

namespace app\modules\files\uploader\controllers;

use app\modules\myclass\FileUploader;
use support\Request;

class UploaderController
{
    // 普通文件上传（适合小文件）
    public function uploadImage(Request $request)
    {
        try {
            $file = $request->file('fileData');
            if (!$file) {
                return error(trans('not_find_file'), not_find_file);
            }

            // 生成简短且唯一的文件名（示例：`abc123.jpg`）
            $extension = $file->getUploadExtension(); // 获取文件扩展名（如 `jpg`）
            $newName = substr(md5(uniqid()), 0, 6) . '.' . $extension; // 6位随机字符串 + 扩展名

            $driver = $request->get('driver', null);
            $tmpPath = $file->getPathname();

            $uploader = new FileUploader($driver);
            $result = $uploader->upload($tmpPath, $newName); // 使用新文件名上传

            return success($result);
        } catch (\Exception $e) {
            return error(trans('fail_to_upload'), fail_to_upload);
        }
    }

    public function getAliYunSignature()
    {
        return success(feature('myclass.FileAliyun.getSignature'));
    }

    // 大视频分片上传接口
    public function uploadVideoChunk(Request $request)
    {
        try {
            $file = $request->file('chunk');
            if (!$file) {
                return error(trans('not_find_file'), not_find_file);
            }

            $driver = $request->get('driver', null);
            $uploader = new FileUploader($driver);

            // 获取分片信息
            $chunkNumber = $request->post('chunkNumber', 1);
            $totalChunks = $request->post('totalChunks', 1);
            $chunkSize = $request->post('chunkSize', 0);
            $totalSize = $request->post('totalSize', 0);
            $identifier = $request->post('identifier', '');
            $filename = $request->post('filename', '');

            // 验证参数
            if (empty($identifier) || empty($filename)) {
                return error(trans('invalid_parameters'), invalid_parameters);
            }

            // 处理分片上传
            $result = $uploader->uploadChunk(
                $file->getPathname(),
                $filename,
                $identifier,
                $chunkNumber,
                $totalChunks,
                $chunkSize,
                $totalSize
            );

            return success($result);
        } catch (\Exception $e) {
            return error($e->getMessage(), fail_to_upload);
        }
    }

    // 检查分片状态（用于断点续传）
    public function checkChunks(Request $request)
    {
        try {
            $driver = $request->get('driver', null);
            $uploader = new FileUploader($driver);

            $identifier = $request->post('identifier', '');
            $filename = $request->post('filename', '');
            $totalChunks = $request->post('totalChunks', 0);

            if (empty($identifier) || empty($filename)) {
                return error(trans('invalid_parameters'), invalid_parameters);
            }
            $result = $uploader->checkChunks($identifier, $filename, $totalChunks);

            return success($result);
        } catch (\Exception $e) {
            return error($e->getMessage() . '-' . $e->getLine() . $e->getFile(), fail_to_upload);
        }
    }

    // 合并分片
    public function mergeChunks(Request $request)
    {
        try {
            $driver = $request->get('driver', null);
            $uploader = new FileUploader($driver);

            $identifier = $request->post('identifier', '');
            $filename = $request->post('filename', '');
            $totalChunks = $request->post('totalChunks', 0);
            $totalSize = $request->post('totalSize', 0);

            if (empty($identifier) || empty($filename)) {
                return error(trans('invalid_parameters'), invalid_parameters);
            }

            $result = $uploader->mergeChunks($identifier, $filename, $totalChunks, $totalSize);

            return success($result);
        } catch (\Exception $e) {
            return error($e->getMessage(), fail_to_upload);
        }
    }
}
