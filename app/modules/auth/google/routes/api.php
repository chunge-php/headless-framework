<?php

use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\auth\google\controllers\GoogleController;
Route::post('/google/googleAuthUrl', [GoogleController::class, 'googleAuthUrl']);//谷歌登录链接获取
Route::post('/google/callback', [GoogleController::class, 'authGoogleCallback']); //谷歌登录/注册
Route::post('/google/bind', [GoogleController::class, 'bind'])->name('google.bind')->middleware([JwtMiddleware::class]);