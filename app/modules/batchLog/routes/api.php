<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\batchLog\controllers\BatchlogController;
Route::group('/batchLog', function () {
    Route::get('/index',   [BatchlogController::class, 'index'])->name('batchLog.index');   //列表
    Route::get('/show',   [BatchlogController::class, 'show'])->name('batchLog.show');   //详情
    Route::post('/delete',   [BatchlogController::class, 'delete'])->name('batchLog.delete');   //删除
})->middleware([JwtMiddleware::class]);
