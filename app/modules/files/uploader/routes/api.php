<?php

use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\files\uploader\controllers\UploaderController;

Route::group('/files/uploader', function () {
    Route::any('/image', [UploaderController::class, 'uploadImage'])->name('files.uploader.image'); //上传图片
    Route::any('/uploadVideoChunk', [UploaderController::class, 'uploadVideoChunk'])->name('files.uploader.uploadVideoChunk'); //上传图片
    Route::any('/checkChunks', [UploaderController::class, 'checkChunks'])->name('files.uploader.checkChunks'); //上传图片
    Route::any('/mergeChunks', [UploaderController::class, 'mergeChunks'])->name('files.uploader.mergeChunks'); //上传图片
    Route::any('/get/signature', [UploaderController::class, 'getAliYunSignature'])->name('files.uploader.getAliYunSignature'); //获取阿里云上传文件签名
    
})->middleware([JwtMiddleware::class]);
