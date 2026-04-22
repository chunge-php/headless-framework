<?php

use app\modules\user\wallet\controllers\BillController;
use Webman\Route;
use app\modules\user\wallet\controllers\UserWalletController;
use app\middleware\JwtMiddleware;


Route::group('/user/wallet', function () {
    Route::get('/getBalance', [UserWalletController::class, 'getBalance'])->name('user.wallet.getBalance');
    Route::get('/billIndex', [BillController::class, 'index'])->name('user.bill.billIndex');
    Route::post('/setAutoTopup', [UserWalletController::class, 'setAutoTopup'])->name('user.wallet.setAutoTopup');//设置自动充值
    Route::post('/setReminderPrice', [UserWalletController::class, 'setReminderPrice'])->name('user.wallet.setReminderPrice');//设置低余额提醒邮件
    Route::post('/recharge', [UserWalletController::class, 'recharge'])->name('user.wallet.recharge');//手动充值
    Route::get('/getAutoTopup', [UserWalletController::class, 'getAutoTopup'])->name('user.wallet.getAutoTopup');//获取自动充值
    Route::get('/autoTopupIndex', [UserWalletController::class, 'autoTopupIndex'])->name('user.wallet.autoTopupIndex');//获取充值账单记录
    
})->middleware([JwtMiddleware::class]);
