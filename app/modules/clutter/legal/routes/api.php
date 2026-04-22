<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\clutter\legal\controllers\LegalController;
Route::group('/clutter/legal', function () {
    Route::get('/index',   [LegalController::class, 'index'])->name('clutter.legal.index');   //列表
    Route::post('/create',   [LegalController::class, 'create'])->name('clutter.legal.create');   //创建/修改
    Route::get('/show',   [LegalController::class, 'show'])->name('clutter.legal.show');   //详情
    Route::post('/delete',   [LegalController::class, 'delete'])->name('clutter.legal.delete');   //删除
})->middleware([JwtMiddleware::class]);
Route::get('/clutter/legal/slugName',   [LegalController::class, 'slugName'])->name('clutter.legal.slugName');   //删除

