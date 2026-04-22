<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\user\card\controllers\CardController;
Route::group('/user/card', function () {
    Route::get('/index',   [CardController::class, 'index'])->name('user.card.index');   //列表
    Route::post('/create',   [CardController::class, 'create'])->name('user.card.create');   //创建/修改
    Route::get('/show',   [CardController::class, 'show'])->name('user.card.show');   //详情
    Route::post('/delete',   [CardController::class, 'delete'])->name('user.card.delete');   //删除
    Route::post('/defaultState',   [CardController::class, 'defaultState'])->name('user.card.defaultState');   //设置默认扣款卡
    Route::post('/setState',   [CardController::class, 'setState'])->name('user.card.setState');   //设置卡禁用或启用
    
})->middleware([JwtMiddleware::class]);
