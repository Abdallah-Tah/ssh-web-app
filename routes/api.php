<?php

use App\Http\Controllers\Api\V1\SSHController;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware(['auth:sanctum', 'throttle:ssh-operations'])->group(function () {
        Route::post('/ssh/connect', [SSHController::class, 'connect'])->name('ssh.connect');
        Route::post('/ssh/execute', [SSHController::class, 'execute'])->name('ssh.execute');
        Route::get('/ssh/metrics', [SSHController::class, 'metrics'])->name('ssh.metrics');
    });
});
