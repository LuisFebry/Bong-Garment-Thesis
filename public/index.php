<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Request::capture()
    )->send();
    $kernel->terminate($request, $response);
} else {
    // Fallback if vendor is not yet installed (e.g., direct web server viewing):
    // Serve the standalone full CMT interactive prototype
    if (file_exists(__DIR__.'/../index.html')) {
        include __DIR__.'/../index.html';
    } else {
        echo '<h1>Bong Garment CMT System</h1><p>Please run composer install or open index.html in your browser.</p>';
    }
}

