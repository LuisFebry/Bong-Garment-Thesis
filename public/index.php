<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $app->handleRequest(Request::capture());
} else {
    if (file_exists(__DIR__.'/../index.html')) {
        include __DIR__.'/../index.html';
    } else {
        echo '<h1>Bong Garment CMT System</h1><p>Please run composer install or open index.html in your browser.</p>';
    }
}
