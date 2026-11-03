<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class EnsureDatabaseMigrated
{
    private static bool $checked = false;

    /**
     * Ensure all 17 tables are migrated and seeded before handling web requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!self::$checked) {
            try {
                if (!Schema::hasTable('Pesanan')) {
                    Artisan::call('migrate:fresh', [
                        '--force' => true,
                        '--seed' => true,
                    ]);
                }
                self::$checked = true;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }
}
