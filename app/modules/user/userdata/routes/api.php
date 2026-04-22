<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\user\userdata\controllers\UserdataController;
Route::group('/user/userdata', function () {
    Route::post('/create',   [UserdataController::class, 'create'])->name('user.userdata.create');   //创建/修改
    Route::get('/show',   [UserdataController::class, 'show'])->name('user.userdata.show');   //详情
})->middleware([JwtMiddleware::class]);
