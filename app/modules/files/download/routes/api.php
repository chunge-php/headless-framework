<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\files\download\controllers\DownloadController;
Route::group('/files/download', function () {
    Route::get('/templatDownload',   [DownloadController::class, 'templatDownload'])->name('files.download.templatDownload');   //下载预设模板
    Route::get('/customDownload',   [DownloadController::class, 'customDownload'])->name('files.download.customDownload');   //下载前端自定义模板
});

