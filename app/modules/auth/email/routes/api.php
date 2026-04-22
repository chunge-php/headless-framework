<?php

use app\middleware\JwtMiddleware;
use app\modules\auth\email\controllers\EmailController;
use Webman\Route;

Route::group('/auth/email', function () {
    Route::post('/sendRegister',   [EmailController::class, 'sendRegister'])->name('auth.email.sendRegister');   // {email, scene}
    Route::post('/bind',   [EmailController::class, 'bind'])->name('auth.email.bind')->middleware([JwtMiddleware::class]);   // {email, scene}
});
