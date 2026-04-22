<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\templat\controllers\TemplatController;
Route::group('/templat', function () {
    Route::get('/index',   [TemplatController::class, 'index'])->name('templat.index');   //列表
    Route::get('/getNameIndex',   [TemplatController::class, 'getNameIndex'])->name('templat.getNameIndex');   //列表
    
    Route::post('/create',   [TemplatController::class, 'create'])->name('templat.create');   //创建/修改
    Route::get('/show',   [TemplatController::class, 'show'])->name('templat.show');   //详情
    Route::post('/delete',   [TemplatController::class, 'delete'])->name('templat.delete');   //删除
})->middleware([JwtMiddleware::class]);
