<?php

namespace Tests\Feature;

use App\Http\Controllers\StokBarangJadiController;
use App\Http\Middleware\RoleMiddleware;
use App\Models\DetailRollBahan;
use App\Models\PemotonganSeri;
use App\Models\Pesanan;
use App\Models\Qc;
use App\Models\RiwayatStokBarangJadi;
use App\Models\StokBarangJadi;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnitAndIntegrationTest extends TestCase
{
    /**
     * UI-01: EDF queue sorting in PemotonganSeriController & DashboardController uses COALESCE(deadline_terbaru, deadline_awal) ASC.
     */
    public function test_edf_queue_sorting_in_pemotongan_and_dashboard_uses_coalesce_deadline_asc(): void
    {
        $pemotongRes = $this->actingAsRole('Pemotong')->getJson('/pemotongan');
        $pemotongRes->assertOk()->assertJson(['success' => true]);

        $orderIds = array_column($pemotongRes->json('data.orders'), 'id_pesanan');
        $this->assertSame([1, 2, 3, 4], $orderIds);

        $dashRes = $this->actingAsRole('Admin')->getJson('/dashboard');
        $dashRes->assertOk()->assertJson(['success' => true]);

        $dashQueueIds = array_column($dashRes->json('data.edfQueue'), 'id_pesanan');
        $this->assertSame([1, 2, 3, 4], $dashQueueIds);

        Pesanan::where('id_pesanan', 1)->update(['status_pesanan' => 'Selesai']);

        $dashAfterRes = $this->actingAsRole('Admin')->getJson('/dashboard');
        $dashAfterRes->assertOk();
        $dashAfterIds = array_column($dashAfterRes->json('data.edfQueue'), 'id_pesanan');
        $this->assertSame([2, 3, 4], $dashAfterIds);
    }

    /**
     * UI-02: EDF queue reorders dynamically when deadline_terbaru is revised.
     */
    public function test_edf_queue_reorders_dynamically_after_deadline_revision(): void
    {
        $reviseRes = $this->actingAsRole('Admin')->postJson('/pesanan/4/revise-deadline', [
            'deadline_terbaru' => '2026-11-05',
            'alasan_perubahan_deadline' => 'Percepatan pengiriman mitra prioritas',
        ]);

        $reviseRes->assertOk()->assertJson(['success' => true]);

        $order4 = Pesanan::findOrFail(4);
        $this->assertSame('2026-11-05', $order4->deadline_terbaru->toDateString());
        $this->assertSame('Percepatan pengiriman mitra prioritas', $order4->alasan_perubahan_deadline);
        $this->assertNotNull($order4->tgl_perubahan_deadline);

        $pemotongRes = $this->actingAsRole('Pemotong')->getJson('/pemotongan');
        $pemotongRes->assertOk();
        $this->assertSame([4, 1, 2, 3], array_column($pemotongRes->json('data.orders'), 'id_pesanan'));

        $dashRes = $this->actingAsRole('Admin')->getJson('/dashboard');
        $dashRes->assertOk();
        $this->assertSame([4, 1, 2, 3], array_column($dashRes->json('data.edfQueue'), 'id_pesanan'));
    }

    /**
     * UI-03: PemotonganSeriController@store atomically inserts cutting hierarchy, deducts roll, and updates Pesanan status.
     */
    public function test_cutting_store_executes_atomic_transaction_and_updates_roll_and_order_status(): void
    {
        $res = $this->actingAsRole('Pemotong')->postJson('/pemotongan', [
            'id_pesanan' => 2,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-2026-UI03',
            'jenis_variasi' => 'Kemeja Oxford Putih Batch 1',
            'tgl_potong' => '2026-11-03 09:00:00',
            'total_lembar_hasil' => 120,
            'potong_warna' => [
                [
                    'id_detail_warna' => 3,
                    'total_lembar_direncanakan' => 120,
                    'kain_terpakai' => 40.00,
                    'gelaran' => [
                        [
                            'id_roll' => 8,
                            'panjang_gelar' => 2.50,
                            'jumlah_tumpuk' => 60,
                            'total_lembar_gelar' => 120,
                            'status_roll_baru' => 'Dipotong',
                        ],
                    ],
                ],
            ],
        ]);

        $res->assertCreated()->assertJson(['success' => true]);
        $idPotong = $res->json('data.id_potong');

        $this->assertDatabaseHas('Pemotongan_Seri', [
            'id_potong' => $idPotong,
            'id_pesanan' => 2,
            'kode_seri' => 'SERI-2026-UI03',
            'total_lembar_hasil' => 120,
        ]);
        $this->assertDatabaseHas('Detail_Pemotongan_Warna', [
            'id_potong' => $idPotong,
            'id_detail_warna' => 3,
            'total_lembar_direncanakan' => 120,
            'kain_terpakai' => 40.00,
        ]);

        $potongWarnaId = DB::table('Detail_Pemotongan_Warna')
            ->where('id_potong', $idPotong)
            ->value('id_potong_warna');

        $this->assertDatabaseHas('Detail_Gelaran_Kain', [
            'id_potong_warna' => $potongWarnaId,
            'id_roll' => 8,
            'total_lembar_gelar' => 120,
        ]);

        $roll8 = DetailRollBahan::findOrFail(8);
        $this->assertSame('80.00', (string) $roll8->ukuran_sisa);
        $this->assertSame('Dipotong', $roll8->status_roll);

        $order2 = Pesanan::findOrFail(2);
        $this->assertSame('Pemotongan', $order2->status_pesanan);
    }

    /**
     * UI-04: PemotonganSeriController@store marks roll Habis when remainder <= 0.05 and Dipotong otherwise.
     */
    public function test_cutting_store_sets_roll_habis_at_0_05_threshold_and_dipotong_above_it(): void
    {
        // Roll #2 starts at 25.00 Kg -> cutting 24.95 leaves 0.05 (<= 0.05 -> Habis, ukuran_sisa = 0.00)
        $resExactBoundary = $this->actingAsRole('Pemotong')->postJson('/pemotongan', [
            'id_pesanan' => 3,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-BOUNDARY-005',
            'total_lembar_hasil' => 100,
            'potong_warna' => [
                [
                    'id_detail_warna' => 1,
                    'total_lembar_direncanakan' => 100,
                    'kain_terpakai' => 24.95,
                    'gelaran' => [
                        [
                            'id_roll' => 2,
                            'panjang_gelar' => 2.50,
                            'jumlah_tumpuk' => 50,
                            'total_lembar_gelar' => 100,
                            'status_roll_baru' => 'Dipotong',
                        ],
                    ],
                ],
            ],
        ]);
        $resExactBoundary->assertCreated();

        $roll2 = DetailRollBahan::findOrFail(2);
        $this->assertSame('0.00', (string) $roll2->ukuran_sisa);
        $this->assertSame('Habis', $roll2->status_roll);

        // Roll #3 starts at 25.00 Kg -> cutting 24.96 leaves 0.04 (<= 0.05 -> Habis, ukuran_sisa = 0.00)
        $resBelowBoundary = $this->actingAsRole('Pemotong')->postJson('/pemotongan', [
            'id_pesanan' => 3,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-BOUNDARY-004',
            'total_lembar_hasil' => 100,
            'potong_warna' => [
                [
                    'id_detail_warna' => 1,
                    'total_lembar_direncanakan' => 100,
                    'kain_terpakai' => 24.96,
                    'gelaran' => [
                        [
                            'id_roll' => 3,
                            'panjang_gelar' => 2.50,
                            'jumlah_tumpuk' => 50,
                            'total_lembar_gelar' => 100,
                            'status_roll_baru' => 'Dipotong',
                        ],
                    ],
                ],
            ],
        ]);
        $resBelowBoundary->assertCreated();

        $roll3 = DetailRollBahan::findOrFail(3);
        $this->assertSame('0.00', (string) $roll3->ukuran_sisa);
        $this->assertSame('Habis', $roll3->status_roll);

        // Roll #5 starts at 25.00 Kg -> cutting 24.94 leaves 0.06 (> 0.05 -> Dipotong, ukuran_sisa = 0.06)
        $resAboveBoundary = $this->actingAsRole('Pemotong')->postJson('/pemotongan', [
            'id_pesanan' => 3,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-BOUNDARY-006',
            'total_lembar_hasil' => 100,
            'potong_warna' => [
                [
                    'id_detail_warna' => 2,
                    'total_lembar_direncanakan' => 100,
                    'kain_terpakai' => 24.94,
                    'gelaran' => [
                        [
                            'id_roll' => 5,
                            'panjang_gelar' => 2.50,
                            'jumlah_tumpuk' => 50,
                            'total_lembar_gelar' => 100,
                            'status_roll_baru' => 'Dipotong',
                        ],
                    ],
                ],
            ],
        ]);
        $resAboveBoundary->assertCreated();

        $roll5 = DetailRollBahan::findOrFail(5);
        $this->assertSame('0.06', (string) $roll5->ukuran_sisa);
        $this->assertSame('Dipotong', $roll5->status_roll);
    }

    /**
     * UI-05: PemotonganSeriController@destroy reverses roll stock on delete (full restore, partial restore, and optional skip).
     */
    public function test_cutting_destroy_reverses_roll_stock_on_delete_and_supports_partial_or_no_reverse(): void
    {
        // 1. Single cut on Roll #8 -> delete restores to Tersedia 120.00
        $cut1 = $this->actingAsRole('Pemotong')->postJson('/pemotongan', [
            'id_pesanan' => 2,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-DEL-01',
            'total_lembar_hasil' => 80,
            'potong_warna' => [[
                'id_detail_warna' => 3,
                'total_lembar_direncanakan' => 80,
                'kain_terpakai' => 40.00,
                'gelaran' => [[
                    'id_roll' => 8,
                    'panjang_gelar' => 2.50,
                    'jumlah_tumpuk' => 40,
                    'total_lembar_gelar' => 80,
                ]],
            ]],
        ]);
        $cut1->assertCreated();
        $idPotong1 = $cut1->json('data.id_potong');

        $del1 = $this->actingAsRole('Pemotong')->postJson("/pemotongan/{$idPotong1}/delete");
        $del1->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('Pemotongan_Seri', ['id_potong' => $idPotong1]);
        $this->assertDatabaseMissing('Detail_Pemotongan_Warna', ['id_potong' => $idPotong1]);
        $roll8 = DetailRollBahan::findOrFail(8);
        $this->assertSame('120.00', (string) $roll8->ukuran_sisa);
        $this->assertSame('Tersedia', $roll8->status_roll);

        // 2. Two cuts on Roll #9 (30.00 + 20.00 -> 70.00) -> delete second restores 90.00 and stays Dipotong
        $cut2a = $this->actingAsRole('Pemotong')->postJson('/pemotongan', [
            'id_pesanan' => 2,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-DEL-02A',
            'total_lembar_hasil' => 60,
            'potong_warna' => [[
                'id_detail_warna' => 3,
                'total_lembar_direncanakan' => 60,
                'kain_terpakai' => 30.00,
                'gelaran' => [[
                    'id_roll' => 9,
                    'panjang_gelar' => 2.50,
                    'jumlah_tumpuk' => 30,
                    'total_lembar_gelar' => 60,
                ]],
            ]],
        ]);
        $cut2a->assertCreated();

        $cut2b = $this->actingAsRole('Pemotong')->postJson('/pemotongan', [
            'id_pesanan' => 2,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-DEL-02B',
            'total_lembar_hasil' => 40,
            'potong_warna' => [[
                'id_detail_warna' => 3,
                'total_lembar_direncanakan' => 40,
                'kain_terpakai' => 20.00,
                'gelaran' => [[
                    'id_roll' => 9,
                    'panjang_gelar' => 2.50,
                    'jumlah_tumpuk' => 20,
                    'total_lembar_gelar' => 40,
                ]],
            ]],
        ]);
        $cut2b->assertCreated();
        $idPotong2b = $cut2b->json('data.id_potong');

        $del2b = $this->actingAsRole('Pemotong')->postJson("/pemotongan/{$idPotong2b}/delete");
        $del2b->assertOk();

        $roll9 = DetailRollBahan::findOrFail(9);
        $this->assertSame('90.00', (string) $roll9->ukuran_sisa);
        $this->assertSame('Dipotong', $roll9->status_roll);

        // 3. Delete with reverse_roll = false leaves Roll #10 at 70.00 Dipotong
        $cut3 = $this->actingAsRole('Pemotong')->postJson('/pemotongan', [
            'id_pesanan' => 2,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-DEL-03',
            'total_lembar_hasil' => 100,
            'potong_warna' => [[
                'id_detail_warna' => 4,
                'total_lembar_direncanakan' => 100,
                'kain_terpakai' => 50.00,
                'gelaran' => [[
                    'id_roll' => 10,
                    'panjang_gelar' => 2.50,
                    'jumlah_tumpuk' => 50,
                    'total_lembar_gelar' => 100,
                ]],
            ]],
        ]);
        $cut3->assertCreated();
        $idPotong3 = $cut3->json('data.id_potong');

        $del3 = $this->actingAsRole('Pemotong')->postJson("/pemotongan/{$idPotong3}/delete", [
            'reverse_roll' => false,
        ]);
        $del3->assertOk();

        $roll10 = DetailRollBahan::findOrFail(10);
        $this->assertSame('70.00', (string) $roll10->ukuran_sisa);
        $this->assertSame('Dipotong', $roll10->status_roll);
    }

    /**
     * UI-06: QcController@store rejects jumlah_lolos + jumlah_cacat != jumlah_diperiksa with HTTP 422.
     */
    public function test_qc_store_rejects_invalid_quantity_sum_with_422(): void
    {
        $initialQcCount = Qc::count();

        $res = $this->actingAsRole('QC')->postJson('/qc', [
            'id_pesanan' => 3,
            'id_user_qc' => 4,
            'tahap_qc' => 'Jahit Akhir',
            'jumlah_diperiksa' => 100,
            'jumlah_lolos' => 90,
            'jumlah_cacat' => 5,
            'jenis_cacat' => 'Cacat Jahit',
            'status_qc' => 'Need Rework',
        ]);

        $res->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('Validasi Mutu Gagal', (string) $res->json('message'));
        $this->assertSame($initialQcCount, Qc::count());
    }

    /**
     * UI-07: QcController@store adds Jahit Akhir + Lolos QC units to Stok_Barang_Jadi and Riwayat_Stok_Barang_Jadi ONLY when Sumber_Inisiasi is Produk Abadi.
     */
    public function test_qc_jahit_akhir_adds_stock_and_history_only_for_produk_abadi(): void
    {
        $res = $this->actingAsRole('QC')->postJson('/qc', [
            'id_pesanan' => 3, // Produk Abadi, id_pola = 1
            'id_user_qc' => 4,
            'tahap_qc' => 'Jahit Akhir',
            'jumlah_diperiksa' => 50,
            'jumlah_lolos' => 48,
            'jumlah_cacat' => 2,
            'jenis_cacat' => 'Cacat Jahit',
            'status_qc' => 'Lolos QC',
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
        ]);

        $res->assertCreated()->assertJson(['success' => true]);
        $idQc = $res->json('data.id_qc');

        $stock = StokBarangJadi::where([
            'id_pola' => 1,
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
        ])->firstOrFail();
        $this->assertSame(128, (int) $stock->jumlah_stok_pcs); // 80 + 48

        $this->assertDatabaseHas('Riwayat_Stok_Barang_Jadi', [
            'id_stok' => $stock->id_stok,
            'jenis_transaksi' => 'MASUK',
            'jumlah_pcs' => 48,
            'stok_sebelum' => 80,
            'stok_sesudah' => 128,
            'keterangan' => 'Masuk otomatis dari hasil inspeksi QC #' . $idQc,
        ]);

        $this->assertSame('Selesai', Pesanan::findOrFail(3)->status_pesanan);

        // Non-Jahit Akhir stage on Produk Abadi must NOT credit Stok_Barang_Jadi
        $potongRes = $this->actingAsRole('QC')->postJson('/qc', [
            'id_pesanan' => 3,
            'id_user_qc' => 4,
            'tahap_qc' => 'Potong',
            'jumlah_diperiksa' => 20,
            'jumlah_lolos' => 20,
            'jumlah_cacat' => 0,
            'jenis_cacat' => 'Tidak Ada',
            'status_qc' => 'Lolos QC',
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
        ]);
        $potongRes->assertCreated();
        $this->assertSame(128, (int) $stock->fresh()->jumlah_stok_pcs);
    }

    /**
     * UI-08: QcController@store does NOT credit Stok_Barang_Jadi or Riwayat_Stok_Barang_Jadi for Request Mitra or Inisiatif Tren Pasar orders.
     */
    public function test_qc_jahit_akhir_does_not_add_stock_for_request_mitra_or_tren_pasar(): void
    {
        $initialTotalStock = (int) StokBarangJadi::sum('jumlah_stok_pcs');
        $initialHistoryCount = RiwayatStokBarangJadi::count();

        // Pesanan #2 is Request Mitra (id_sumber = 1)
        $mitraRes = $this->actingAsRole('QC')->postJson('/qc', [
            'id_pesanan' => 2,
            'id_user_qc' => 4,
            'tahap_qc' => 'Jahit Akhir',
            'jumlah_diperiksa' => 100,
            'jumlah_lolos' => 100,
            'jumlah_cacat' => 0,
            'jenis_cacat' => 'Tidak Ada',
            'status_qc' => 'Lolos QC',
            'id_spesifikasi' => 6,
            'nama_warna' => 'Putih Bersih',
        ]);
        $mitraRes->assertCreated();

        $this->assertSame('Selesai', Pesanan::findOrFail(2)->status_pesanan);
        $this->assertSame(65, (int) StokBarangJadi::findOrFail(4)->jumlah_stok_pcs);
        $this->assertSame($initialTotalStock, (int) StokBarangJadi::sum('jumlah_stok_pcs'));
        $this->assertSame($initialHistoryCount, RiwayatStokBarangJadi::count());

        // Pesanan #4 is Inisiatif Tren Pasar (id_sumber = 3)
        $trenRes = $this->actingAsRole('QC')->postJson('/qc', [
            'id_pesanan' => 4,
            'id_user_qc' => 4,
            'tahap_qc' => 'Jahit Akhir',
            'jumlah_diperiksa' => 40,
            'jumlah_lolos' => 40,
            'jumlah_cacat' => 0,
            'jenis_cacat' => 'Tidak Ada',
            'status_qc' => 'Lolos QC',
            'id_spesifikasi' => 8,
            'nama_warna' => 'Khaki Beige',
        ]);
        $trenRes->assertCreated();

        $this->assertSame($initialTotalStock, (int) StokBarangJadi::sum('jumlah_stok_pcs'));
        $this->assertSame($initialHistoryCount, RiwayatStokBarangJadi::count());
    }

    /**
     * UI-09: QcController@completeRework transitions Need Rework to Rework Selesai and records tgl_selesai_perbaikan.
     */
    public function test_qc_complete_rework_updates_status_and_records_completion_timestamp(): void
    {
        $createRes = $this->actingAsRole('QC')->postJson('/qc', [
            'id_pesanan' => 2,
            'id_user_qc' => 4,
            'tahap_qc' => 'Jahit Akhir',
            'jumlah_diperiksa' => 60,
            'jumlah_lolos' => 54,
            'jumlah_cacat' => 6,
            'jenis_cacat' => 'Cacat Jahit',
            'pic_rework' => 'Mitra Penjahit CMT',
            'catatan_qc' => 'Obras jahitan bahu lepas',
            'status_qc' => 'Need Rework',
            'tgl_target_perbaikan' => '2026-11-12',
        ]);
        $createRes->assertCreated();
        $idQc = $createRes->json('data.id_qc');

        $qcRecord = Qc::findOrFail($idQc);
        $this->assertSame('Need Rework', $qcRecord->status_qc);
        $this->assertStringStartsWith('[PIC: Mitra Penjahit CMT]', (string) $qcRecord->catatan_qc);
        $this->assertNull($qcRecord->tgl_selesai_perbaikan);
        $this->assertSame('Dalam QC', Pesanan::findOrFail(2)->status_pesanan);

        $completeRes = $this->actingAsRole('QC')->postJson("/qc/{$idQc}/complete-rework", [
            'tgl_selesai_perbaikan' => '2026-11-11 16:30:00',
        ]);
        $completeRes->assertOk()->assertJson(['success' => true]);

        $qcUpdated = Qc::findOrFail($idQc);
        $this->assertSame('Rework Selesai', $qcUpdated->status_qc);
        $this->assertSame('2026-11-11 16:30:00', $qcUpdated->tgl_selesai_perbaikan->format('Y-m-d H:i:s'));
    }

    /**
     * UI-10: QcController@destroy reverts auto-credited Produk Abadi finished goods stock and history.
     */
    public function test_qc_destroy_reverts_produk_abadi_finished_stock_and_auto_history(): void
    {
        $createRes = $this->actingAsRole('QC')->postJson('/qc', [
            'id_pesanan' => 3,
            'id_user_qc' => 4,
            'tahap_qc' => 'Jahit Akhir',
            'jumlah_diperiksa' => 30,
            'jumlah_lolos' => 30,
            'jumlah_cacat' => 0,
            'jenis_cacat' => 'Tidak Ada',
            'status_qc' => 'Lolos QC',
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
        ]);
        $createRes->assertCreated();
        $idQc = $createRes->json('data.id_qc');

        $this->assertSame(110, (int) StokBarangJadi::findOrFail(1)->jumlah_stok_pcs); // 80 + 30

        $delRes = $this->actingAsRole('QC')->postJson("/qc/{$idQc}/delete");
        $delRes->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('QC', ['id_qc' => $idQc]);
        $this->assertSame(80, (int) StokBarangJadi::findOrFail(1)->jumlah_stok_pcs);
        $this->assertDatabaseMissing('Riwayat_Stok_Barang_Jadi', [
            'keterangan' => 'Masuk otomatis dari hasil inspeksi QC #' . $idQc,
        ]);
    }

    /**
     * UI-11: StokBarangJadiController@adjustStock rejects deductions that result in negative stock (HTTP 422).
     */
    public function test_warehouse_adjust_stock_prevents_negative_stock_with_422(): void
    {
        $initialHistoryCount = RiwayatStokBarangJadi::count();

        $res = $this->actingAsRole('Gudang')->postJson('/stok/adjust', [
            'id_pola' => 1,
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
            'delta_pcs' => -81, // current is 80
            'alasan_penyesuaian' => 'Penjualan Langsung Produk Abadi',
        ]);

        $res->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame(80, (int) StokBarangJadi::findOrFail(1)->jumlah_stok_pcs);
        $this->assertSame($initialHistoryCount, RiwayatStokBarangJadi::count());
    }

    /**
     * UI-12: StokBarangJadiController@adjustStock requires nama_mitra when reason involves Mitra (HTTP 422).
     */
    public function test_warehouse_adjust_stock_requires_nama_mitra_when_reason_involves_mitra(): void
    {
        $failRes = $this->actingAsRole('Gudang')->postJson('/stok/adjust', [
            'id_pola' => 1,
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
            'delta_pcs' => -10,
            'alasan_penyesuaian' => 'Diambil Mitra (Serah Terima Request Mitra)',
            'nama_mitra' => '',
        ]);
        $failRes->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('Nama Mitra wajib diisi', (string) $failRes->json('message'));
        $this->assertSame(80, (int) StokBarangJadi::findOrFail(1)->jumlah_stok_pcs);

        $okRes = $this->actingAsRole('Gudang')->postJson('/stok/adjust', [
            'id_pola' => 1,
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
            'delta_pcs' => -10,
            'alasan_penyesuaian' => 'Diambil Mitra (Serah Terima Request Mitra)',
            'nama_mitra' => 'Jupiter',
            'keterangan' => 'SJ-OUT-01',
        ]);
        $okRes->assertOk()->assertJson(['success' => true]);

        $this->assertSame(70, (int) StokBarangJadi::findOrFail(1)->jumlah_stok_pcs);
        $this->assertDatabaseHas('Riwayat_Stok_Barang_Jadi', [
            'id_stok' => 1,
            'jenis_transaksi' => 'KELUAR',
            'jumlah_pcs' => 10,
            'stok_sebelum' => 80,
            'stok_sesudah' => 70,
            'nama_mitra' => 'Jupiter',
            'keterangan' => 'SJ-OUT-01',
        ]);
    }

    /**
     * UI-13: StokBarangJadiController@updateThresholds persists global thresholds and validates non-negative integers.
     */
    public function test_warehouse_update_thresholds_persists_global_values_and_validates_bounds(): void
    {
        $res = $this->actingAsRole('Gudang')->postJson('/stok/thresholds', [
            'min_stok_jadi' => 35,
            'min_stok_aksesoris' => 750,
        ]);

        $res->assertOk()->assertJson([
            'success' => true,
            'data' => [
                'min_stok_jadi' => 35,
                'min_stok_aksesoris' => 750,
            ],
        ]);

        $this->assertSame([
            'min_stok_jadi' => 35,
            'min_stok_aksesoris' => 750,
        ], StokBarangJadiController::getThresholds());

        $badRes = $this->actingAsRole('Gudang')->postJson('/stok/thresholds', [
            'min_stok_jadi' => -1,
            'min_stok_aksesoris' => 500,
        ]);
        $badRes->assertStatus(422);
    }

    /**
     * UI-14: StokBarangJadiController@destroyRiwayat reverses stock balance when deleting KELUAR or MASUK history rows.
     */
    public function test_warehouse_destroy_riwayat_reverses_stock_balance_for_keluar_and_masuk(): void
    {
        // Delete seeded KELUAR row #1 (20 pcs on id_stok = 1 currently at 80) -> becomes 100
        $delKeluar = $this->actingAsRole('Gudang')->postJson('/stok/riwayat/1/delete');
        $delKeluar->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('Riwayat_Stok_Barang_Jadi', ['id_riwayat_stok' => 1]);
        $this->assertSame(100, (int) StokBarangJadi::findOrFail(1)->jumlah_stok_pcs);

        // Add +25 pcs -> 125, then delete the MASUK history row -> reverts back to 100
        $addRes = $this->actingAsRole('Gudang')->postJson('/stok/adjust', [
            'id_pola' => 1,
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
            'delta_pcs' => 25,
            'alasan_penyesuaian' => 'Stock Opname Masuk',
        ]);
        $addRes->assertOk();
        $this->assertSame(125, (int) StokBarangJadi::findOrFail(1)->jumlah_stok_pcs);

        $latestHistory = RiwayatStokBarangJadi::orderBy('id_riwayat_stok', 'desc')->firstOrFail();
        $this->assertSame('MASUK', $latestHistory->jenis_transaksi);

        $delMasuk = $this->actingAsRole('Gudang')->postJson("/stok/riwayat/{$latestHistory->id_riwayat_stok}/delete");
        $delMasuk->assertOk();

        $this->assertDatabaseMissing('Riwayat_Stok_Barang_Jadi', ['id_riwayat_stok' => $latestHistory->id_riwayat_stok]);
        $this->assertSame(100, (int) StokBarangJadi::findOrFail(1)->jumlah_stok_pcs);
    }

    /**
     * UI-15: RoleMiddleware returns 401 for unauthenticated JSON requests and redirects browser requests to /login.
     */
    public function test_rbac_unauthenticated_returns_401_for_json_and_redirect_for_browser(): void
    {
        $this->actingAsGuest()->get('/dashboard')->assertRedirect('/login');
        $this->actingAsGuest()->get('/pesanan')->assertRedirect('/login');

        $this->actingAsGuest()->getJson('/dashboard')
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /**
     * UI-16: RoleMiddleware blocks unauthorized roles with HTTP 403 across all restricted modules.
     */
    public function test_rbac_blocks_unauthorized_roles_with_403_across_modules(): void
    {
        $matrix = [
            'Pemotong' => ['/pesanan', '/penerimaan', '/qc', '/stok', '/laporan', '/pengaturan'],
            'QC' => ['/pesanan', '/penerimaan', '/pemotongan', '/stok', '/laporan', '/pengaturan'],
            'Gudang' => ['/pesanan', '/pemotongan', '/qc', '/laporan', '/pengaturan'],
            'Pemilik' => ['/pesanan', '/penerimaan', '/pemotongan', '/qc', '/aksesoris', '/pengaturan'],
        ];

        foreach ($matrix as $role => $forbiddenRoutes) {
            foreach ($forbiddenRoutes as $route) {
                $this->actingAsRole($role)->get($route)->assertStatus(403);
                $this->actingAsRole($role)->getJson($route)->assertStatus(403)->assertJson(['success' => false]);
            }
        }
    }

    /**
     * UI-17: Admin can simulate another role via bong_garment_role cookie, while non-Admin cannot escalate privilege.
     */
    public function test_rbac_admin_role_simulation_cookie_and_prevents_non_admin_escalation(): void
    {
        // Admin simulating Pemotong: /pemotongan allowed (200), /pesanan forbidden (403)
        $this->actingAsRole('Admin', 'Pemotong')->get('/pemotongan')->assertOk();
        $this->actingAsRole('Admin', 'Pemotong')->get('/pesanan')->assertStatus(403);

        // Admin simulating Pemilik: /laporan allowed (200), mutating POST blocked (403)
        $this->actingAsRole('Admin', 'Pemilik')->get('/laporan')->assertOk();
        $this->actingAsRole('Admin', 'Pemilik')->postJson('/stok/adjust', [])
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        // Non-Admin (Pemotong) spoofing bong_garment_role = Admin must remain blocked (403)
        $this->actingAsRole('Pemotong', 'Admin')->get('/pesanan')->assertStatus(403);
        $this->actingAsRole('Pemotong', 'Admin')->get('/pengaturan')->assertStatus(403);
    }

    /**
     * UI-18: RoleMiddleware enforces strict Read-Only mode for Pemilik across POST, PUT, PATCH, and DELETE.
     */
    public function test_rbac_pemilik_strict_read_only_blocks_post_put_patch_delete_with_403(): void
    {
        $this->actingAsRole('Pemilik')->get('/dashboard')->assertOk();
        $this->actingAsRole('Pemilik')->get('/stok')->assertOk();
        $this->actingAsRole('Pemilik')->get('/laporan')->assertOk();

        $this->actingAsRole('Pemilik')->postJson('/stok/adjust', [])->assertStatus(403);
        $this->actingAsRole('Pemilik')->post('/stok/thresholds', [])->assertStatus(403);
        $this->actingAsRole('Pemilik')->post('/laporan/profile', [])->assertStatus(403);

        $middleware = new RoleMiddleware();
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $req = Request::create('/stok/adjust', $method, [], [
                'bong_garment_auth_role' => 'Pemilik',
                'bong_garment_role' => 'Pemilik',
            ], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

            $response = $middleware->handle($req, fn () => response()->json(['ok' => true]), 'Admin', 'Gudang', 'Pemilik');
            $this->assertSame(403, $response->getStatusCode(), "Expected 403 for Pemilik {$method}");
            $this->assertStringContainsString('Read-Only', (string) $response->getContent());
        }
    }

    /**
     * UI-19: ON DELETE RESTRICT referential integrity prevents deleting referenced Supplier, Pola, Detail_Roll_Bahan, and Pesanan records.
     */
    public function test_referential_integrity_on_delete_restrict_blocks_referenced_master_and_roll_records(): void
    {
        $targets = [
            ['Supplier', 'id_supplier', 1],
            ['Pola', 'id_pola', 1],
            ['Detail_Roll_Bahan', 'id_roll', 1],
            ['Pesanan', 'id_pesanan', 1],
        ];

        foreach ($targets as [$table, $pk, $id]) {
            try {
                DB::table($table)->where($pk, $id)->delete();
                $this->fail("Expected QueryException (ON DELETE RESTRICT) when deleting {$table} #{$id}.");
            } catch (QueryException $e) {
                $this->assertSame('23000', (string) $e->getCode());
            }
        }

        // Controller-level referential integrity guards return HTTP 422 and keep data intact
        $this->actingAsRole('Admin')->postJson('/supplier/1/delete')->assertStatus(422)->assertJson(['success' => false]);
        $this->actingAsRole('Admin')->postJson('/pola/1/delete')->assertStatus(422)->assertJson(['success' => false]);
        $this->actingAsRole('Admin')->postJson('/penerimaan/roll/1/delete')->assertStatus(422)->assertJson(['success' => false]);
        $this->actingAsRole('Admin')->postJson('/pesanan/1/delete')->assertStatus(422)->assertJson(['success' => false]);

        $this->assertDatabaseHas('Supplier', ['id_supplier' => 1]);
        $this->assertDatabaseHas('Pola', ['id_pola' => 1]);
        $this->assertDatabaseHas('Detail_Roll_Bahan', ['id_roll' => 1]);
        $this->assertDatabaseHas('Pesanan', ['id_pesanan' => 1]);
    }
}
