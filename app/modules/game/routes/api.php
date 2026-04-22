<?php

use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\game\controllers\GameController;

Route::group('/game', function () {
    Route::get('/index',   [GameController::class, 'index'])->name('game.index');   //列表
    Route::post('/create',   [GameController::class, 'create'])->name('game.create');   //创建/修改
    Route::get('/show',   [GameController::class, 'show'])->name('game.show');   //详情
    Route::post('/delete',   [GameController::class, 'delete'])->name('game.delete');   //删除
    Route::post('/setMy',   [GameController::class, 'setMy'])->name('game.setMy');   //配置我的游戏
    Route::get('/getMy',   [GameController::class, 'getMy'])->name('game.getMy');   //获取我的游戏
    Route::get('/getIndexLog',   [GameController::class, 'getIndexLog'])->name('game.getIndexLog');
    Route::get('/getParticipantIndex',   [GameController::class, 'getParticipantIndex'])->name('game.getParticipantIndex'); //获取参与者列表
})->middleware([JwtMiddleware::class]);
Route::get('/game/api/show',   [GameController::class, 'apiShow'])->name('game.apiShow');   //根据Id获取游戏配置
Route::post('/game/api/getJobResult',   [GameController::class, 'getJobResult'])->name('game.getJobResult');   //查询结果
Route::post('/game/api/verCode',   [GameController::class, 'verCode'])->name('game.verCode');   //提交验证码
Route::post('/game/api/sendCode',   [GameController::class, 'sendCode'])->name('game.sendCode');   //发送验证码
Route::post('/game/api/sendSmsResult',   [GameController::class, 'sendSmsResult'])->name('game.sendSmsResult');   //发送短信结果
