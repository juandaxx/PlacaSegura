<?php

use Illuminate\Support\Facades\Route;

Route::get('/connection-status', function () {
    return response()->json([
        'connected' => true,
        'message' => 'Angular se está comunicando con Laravel.',
        'backend' => 'Laravel',
        'timestamp' => now()->toIso8601String(),
    ]);
});
