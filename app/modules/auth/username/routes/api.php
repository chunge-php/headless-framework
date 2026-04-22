<?php
use Webman\Route;
use app\modules\auth\username\controllers\UsernameController;
Route::post('/auth/username/unifyLogin', [UsernameController::class, 'unifyLogin']);
