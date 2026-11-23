<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', function () {
    return view('welcome');
});

// Documentacion interactiva de la API (Swagger UI sobre public/openapi.yaml).
// No usa sesion, asi que se puede ver aunque la base de datos no este disponible.
Route::view('/docs', 'documentacion')
    ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class])
    ->name('documentacion');
