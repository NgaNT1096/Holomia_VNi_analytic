<?php

use App\Http\Controllers\AnalyticsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('analytics', [AnalyticsController::class, 'index']);
Route::get('analytics/{date}', [AnalyticsController::class, 'analytics']);

Route::get('dashboard/analytics/day={day}', [AnalyticsController::class, 'getByDay'])->name('analytics.getByDay');
Route::get('dashboard/analytics/day={day}/{title}', [AnalyticsController::class, 'queryDashboard'])->name('analytics.queryDashboard');
Route::get('dashboard/analytics/sale/day={day}/{title}', [AnalyticsController::class, 'queryDashboardSale'])->name('analytics.sale.queryDashboard');
Route::get('dashboard/analytics/{title}/sale_viewer', [AnalyticsController::class, 'sale_viewer'])->name('analytics.sale_viewer');
