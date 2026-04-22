<?php

use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\user\userInfo\controllers\UserUserinfoController;

Route::get('/user/userInfo/test', [UserUserinfoController::class, 'test']);


Route::group('/user', function () {
    Route::post('/setPicture', [UserUserinfoController::class, 'setPicture'])->name('user.setPicture'); //更新头像
    Route::post('/upUserInfo', [UserUserinfoController::class, 'upUserInfo'])->name('user.upUserInfo'); //更新用户信息或密码
    Route::post('/updateEmail', [UserUserinfoController::class, 'updateEmail'])->name('user.updateEmail'); //更新邮箱
    Route::get('/getUserInfo', [UserUserinfoController::class, 'getUserInfo'])->name('user.getUserInfo'); //获取登录信息
    Route::get('/index', [UserUserinfoController::class, 'index'])->name('user.index'); //获取登录信息
    Route::post('/getUserIdToken', [UserUserinfoController::class, 'getUserIdToken'])->name('user.getUserIdToken'); //根据用户id生成token
    Route::post('/createBasic', [UserUserinfoController::class, 'createBasic'])->name('user.createBasic'); //创建用户基本信息
    Route::get('/show', [UserUserinfoController::class, 'show'])->name('user.show'); //用户详情
    Route::post('/createAccount', [UserUserinfoController::class, 'createAccount'])->name('user.createAccount'); //创建用户账号
})->middleware([JwtMiddleware::class]);
