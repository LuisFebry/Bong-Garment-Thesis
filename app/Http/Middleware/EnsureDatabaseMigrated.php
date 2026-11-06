<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureDatabaseMigrated
{
    private static bool $checked = false;

    /**
     * Ensure all 18 tables are migrated and bootstrapped before handling web requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('x-forwarded-proto') === 'https' || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        if (!self::$checked) {
            try {
                if (!Schema::hasTable('Pesanan')) {
                    Artisan::call('migrate', ['--force' => true]);
                    Artisan::call('db:seed', [
                        '--class' => \Database\Seeders\ProductionBootstrapSeeder::class,
                        '--force' => true,
                    ]);
                } else {
                    try {
                        if (!Schema::hasTable('Riwayat_Stok_Barang_Jadi')) {
                            Schema::create('Riwayat_Stok_Barang_Jadi', function (\Illuminate\Database\Schema\Blueprint $table) {
                                $table->id('id_riwayat_stok');
                                $table->unsignedBigInteger('id_stok')->nullable()->index();
                                $table->unsignedBigInteger('id_pola')->index();
                                $table->unsignedBigInteger('id_spesifikasi')->index();
                                $table->string('nama_warna', 50);
                                $table->enum('jenis_transaksi', ['MASUK', 'KELUAR'])->default('KELUAR');
                                $table->integer('jumlah_pcs');
                                $table->integer('stok_sebelum')->default(0);
                                $table->integer('stok_sesudah')->default(0);
                                $table->string('kategori_alasan', 100);
                                $table->string('nama_mitra', 100)->nullable();
                                $table->string('keterangan', 255)->nullable();
                                $table->unsignedBigInteger('id_user')->nullable()->index();
                                $table->dateTime('tgl_transaksi');
                            });
                        }
                        DB::statement("ALTER TABLE Detail_Pemotongan_Warna MODIFY kain_terpakai DECIMAL(10,2) NULL");
                        DB::statement("ALTER TABLE Detail_Roll_Bahan MODIFY ukuran_awal DECIMAL(10,2) NOT NULL");
                        DB::statement("ALTER TABLE Detail_Roll_Bahan MODIFY ukuran_sisa DECIMAL(10,2) NOT NULL");
                        DB::statement("ALTER TABLE Detail_Bahan_Warna MODIFY total_ukuran DECIMAL(10,2) NOT NULL DEFAULT 0.00");
                        DB::statement("UPDATE Pesanan SET deadline_awal = CURDATE() WHERE deadline_awal IS NULL");
                        DB::statement("UPDATE Pesanan SET deadline_terbaru = deadline_awal WHERE deadline_terbaru IS NULL");
                        DB::statement("ALTER TABLE Pesanan MODIFY deadline_awal DATE NOT NULL COMMENT 'Target batas waktu wajib untuk algoritma EDF'");
                        DB::statement("ALTER TABLE Pesanan MODIFY deadline_terbaru DATE NOT NULL COMMENT 'Target revisi terbaru untuk prioritas antrean EDF'");
                        DB::table('User')->where('nama_user', 'LIKE', '%Luis Febry%')->update(['nama_user' => 'Administrator']);
                    } catch (\Throwable $e) {}
                }
                self::$checked = true;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }
}
