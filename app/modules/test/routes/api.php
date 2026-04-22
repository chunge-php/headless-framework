<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\test\controllers\TestController;
Route::group('/test', function () {
    Route::get('/index',   [TestController::class, 'index'])->name('test.index');   //列表
    Route::post('/create',   [TestController::class, 'create'])->name('test.create');   //创建/修改
    Route::get('/show',   [TestController::class, 'show'])->name('test.show');   //详情
    Route::post('/getAesEncode',   [TestController::class, 'getAesEncode'])->name('test.getAesEncode');   //加密
    Route::post('/getAesDeCode',   [TestController::class, 'getAesDeCode'])->name('test.getAesDeCode');   //解密
    Route::post('/getSignData',   [TestController::class, 'getSignData'])->name('test.getSignData');   //获取api加密签名
    Route::get('/delete',   [TestController::class, 'delete'])->name('test.delete');   //删除
    
});
