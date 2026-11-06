<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
            'bong_garment_auth_role',
            'bong_garment_role',
            'bong_garment_user_id',
            'bong_garment_user_name',
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'login*',
            'logout*',
            'pesanan*',
            'pola*',
            'penerimaan*',
            'supplier*',
            'pemotongan*',
            'qc*',
            'stok*',
            'aksesoris*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();