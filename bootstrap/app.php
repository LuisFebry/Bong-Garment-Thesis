<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\URL;

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    URL::forceScheme('https');
} elseif (env('APP_ENV') === 'production') {
    URL::forceScheme('https');
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\EnsureDatabaseMigrated::class,
        ]);
        $middleware->encryptCookies(except: [
            'bong_garment_role',
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'pesanan*',
            'pola*',
            'penerimaan*',
            'pemotongan*',
            'qc*',
            'stok*',
            'aksesoris*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();