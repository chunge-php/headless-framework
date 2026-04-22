<?php

use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\analysis\controllers\AnalysisController;

Route::group('/analysis', function () {
    Route::get('/overview', [AnalysisController::class, 'overview'])->name('analysis.overview');
    Route::get('/statTrend', [AnalysisController::class, 'statTrend'])->name('analysis.statTrend');
    Route::get('/costTrend', [AnalysisController::class, 'costTrend'])->name('analysis.costTrend');
    Route::get('/kpiSparkline', [AnalysisController::class, 'kpiSparkline'])->name('analysis.kpiSparkline');
    Route::get('/gameStat', [AnalysisController::class, 'gameStat'])->name('analysis.gameStat');
    Route::get('/typeDistribution', [AnalysisController::class, 'typeDistribution'])->name('analysis.typeDistribution');
    Route::get('/recentActivity', [AnalysisController::class, 'recentActivity'])->name('analysis.recentActivity');
})->middleware([JwtMiddleware::class]);
