<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\jobTask\controllers\JobtaskController;
Route::group('/jobTask', function () {
    Route::get('/index',   [JobtaskController::class, 'index'])->name('jobTask.index');   //列表
    Route::post('/create',   [JobtaskController::class, 'create'])->name('jobTask.create');   //创建/修改
    Route::get('/show',   [JobtaskController::class, 'show'])->name('jobTask.show');   //详情
    Route::get('/getKind',   [JobtaskController::class, 'getKind'])->name('jobTask.getKind');   //详情
    Route::get('/futureShow',   [JobtaskController::class, 'futureShow'])->name('jobTask.futureShow');   //获取未来计划列表
    Route::post('/delete',   [JobtaskController::class, 'delete'])->name('jobTask.delete');   //删除
    Route::post('/setStatus',   [JobtaskController::class, 'setStatus'])->name('jobTask.setStatus');   //快捷设置状态
    
})->middleware([JwtMiddleware::class]);
