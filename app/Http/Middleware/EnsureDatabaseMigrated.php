<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureDatabaseMigrated
{
    private static bool $checked = false;

    /**
     * Ensure all 17 tables are migrated and seeded before handling web requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('x-forwarded-proto') === 'https' || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        if (!self::$checked) {
            try {
                // Auto-widen decimal columns for large-batch production data and enforce NOT NULL on EDF deadlines
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE Detail_Pemotongan_Warna MODIFY kain_terpakai DECIMAL(10,2) NULL");
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE Detail_Roll_Bahan MODIFY ukuran_awal DECIMAL(10,2) NOT NULL");
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE Detail_Roll_Bahan MODIFY ukuran_sisa DECIMAL(10,2) NOT NULL");
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE Detail_Bahan_Warna MODIFY total_ukuran DECIMAL(10,2) NOT NULL DEFAULT 0.00");
                    \Illuminate\Support\Facades\DB::statement("UPDATE Pesanan SET deadline_awal = CURDATE() WHERE deadline_awal IS NULL");
                    \Illuminate\Support\Facades\DB::statement("UPDATE Pesanan SET deadline_terbaru = deadline_awal WHERE deadline_terbaru IS NULL");
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE Pesanan MODIFY deadline_awal DATE NOT NULL COMMENT 'Target batas waktu wajib untuk algoritma EDF'");
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE Pesanan MODIFY deadline_terbaru DATE NOT NULL COMMENT 'Target revisi terbaru untuk prioritas antrean EDF'");
                } catch (\Throwable $e) {}

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