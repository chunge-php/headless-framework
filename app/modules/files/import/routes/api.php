<?php

use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\files\import\controllers\ImportController;

Route::group('/files/import', function () {
    Route::post('/unify/templat',   [ImportController::class, 'import'])->name('files.import.sms.template');   //导入短信模板\
})->middleware([JwtMiddleware::class]);
