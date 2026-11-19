<?php

namespace Tests\Feature;

use App\Models\DetailRollBahan;
use App\Models\Pesanan;
use App\Models\Pola;
use App\Models\Qc;
use App\Models\RiwayatStokBarangJadi;
use App\Models\StokBarangJadi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BlackBoxSystemTest extends TestCase
{
    /**
     * BB-01: Login page loads and valid credentials authenticate all 5 Bong Garment roles to their landing routes.
     */
    public function test_bb01_login_page_and_valid_credentials_for_all_five_roles(): void
    {
        $this->actingAsGuest()->get('/login')->assertOk()->assertSee('Autentikasi Pengguna');

        $credentials = [
            ['admin', 'admin123', 'Admin', '/dashboard'],
            ['pemilik', 'pemilik123', 'Pemilik', '/dashboard'],
            ['pemotong', 'potong123', 'Pemotong', '/pemotongan'],
            ['qc', 'qc123', 'QC', '/qc'],
            ['gudang', 'gudang123', 'Gudang', '/penerimaan'],
        ];

        foreach ($credentials as [$username, $password, $expectedRole, $expectedRedirect]) {
            $jsonRes = $this->actingAsGuest()->postJson('/login', [
                'username' => $username,
                'password' => $password,
            ]);

            $jsonRes->assertOk()
                ->assertJson([
                    'success' => true,
                    'redirect' => $expectedRedirect,
                    'user' => [
                        'username' => $username,
                        'role' => $expectedRole,
                    ],
                ])
                ->assertPlainCookie('bong_garment_auth_role', $expectedRole)
                ->assertPlainCookie('bong_garment_role', $expectedRole);

            $webRes = $this->actingAsGuest()->post('/login', [
                'username' => $username,
                'password' => $password,
            ]);
            $webRes->assertRedirect($expectedRedirect);
        }
    }

    /**
     * BB-02: Login rejects wrong password or unknown username (negative case).
     */
    public function test_bb02_login_rejects_wrong_password_or_unknown_username(): void
    {
        $this->actingAsGuest()->postJson('/login', [
            'username' => 'admin',
            'password' => 'salah123',
        ])->assertStatus(401)->assertJson(['success' => false]);

        $this->actingAsGuest()->post('/login', [
            'username' => 'admin',
            'password' => 'salah123',
        ])->assertRedirect()->assertSessionHasErrors('username');

        $this->actingAsGuest()->postJson('/login', [
            'username' => 'tidak_ada',
            'password' => 'admin123',
        ])->assertStatus(401)->assertJson(['success' => false]);
    }

    /**
     * BB-03: Login rejects empty required username/password fields with HTTP 422 (negative case).
     */
    public function test_bb03_login_rejects_empty_required_fields(): void
    {
        $this->actingAsGuest()->postJson('/login', [
            'username' => '',
            'password' => '',
        ])->assertStatus(422)->assertJsonValidationErrors(['username', 'password']);
    }

    /**
     * BB-04: Logout expires authentication cookies and redirects to /login.
     */
    public function test_bb04_logout_clears_session_and_redirects_to_login(): void
    {
        $this->actingAsRole('Admin')->get('/logout')
            ->assertRedirect('/login')
            ->assertCookieExpired('bong_garment_auth_role')
            ->assertCookieExpired('bong_garment_role');

        $this->actingAsRole('Admin')->postJson('/logout')
            ->assertOk()
            ->assertJson(['success' => true, 'redirect' => '/login'])
            ->assertCookieExpired('bong_garment_auth_role');
    }

    /**
     * BB-05: Dashboard renders KPIs, EDF queue, and recent QC feed for authenticated users.
     */
    public function test_bb05_dashboard_renders_kpis_edf_queue_and_recent_qc_for_authenticated_users(): void
    {
        $this->actingAsRole('Admin')->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Produksi CMT')
            ->assertSee('Antrean Pemotongan Pesanan');

        $this->actingAsRole('Admin')->getJson('/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'kpi' => [
                        'total_pesanan_aktif',
                        'total_roll_tersedia',
                        'total_potong_seri',
                        'total_stok_jadi_pcs',
                        'total_qc_need_rework',
                        'total_aksesoris_low',
                    ],
                    'edfQueue',
                    'recentQc',
                ],
            ]);
    }

    /**
     * BB-06: Dashboard blocks unauthenticated visitor (negative case).
     */
    public function test_bb06_dashboard_blocks_unauthenticated_visitor(): void
    {
        $this->actingAsGuest()->get('/dashboard')->assertRedirect('/login');
        $this->actingAsGuest()->getJson('/dashboard')->assertStatus(401);
    }

    /**
     * BB-07: Pesanan page renders and stores a Request Mitra order with initial EDF deadline.
     */
    public function test_bb07_pesanan_index_and_create_request_mitra_order(): void
    {
        $this->actingAsRole('Admin')->get('/pesanan')
            ->assertOk()
            ->assertSee('Kelola Pesanan & Master Pola', false);

        $res = $this->actingAsRole('Admin')->postJson('/pesanan', [
            'id_pola' => 1,
            'id_sumber' => 1,
            'id_penerimaan' => 1,
            'jenis_pesanan' => 'Atasan Baby Terry Salur 8x42',
            'nama_mitra' => 'Jupiter',
            'tgl_order' => '2026-11-11',
            'deadline_awal' => '2026-11-25',
        ]);

        $res->assertCreated()->assertJson(['success' => true]);
        $newId = $res->json('data.id_pesanan');

        $this->assertDatabaseHas('Pesanan', [
            'id_pesanan' => $newId,
            'nama_mitra' => 'Jupiter',
            'deadline_awal' => '2026-11-25',
            'deadline_terbaru' => '2026-11-25',
            'status_pesanan' => 'Antrean',
        ]);
    }

    /**
     * BB-08: Pesanan Produk Abadi forces nama_mitra = null and supports inline new Pola creation.
     */
    public function test_bb08_pesanan_produk_abadi_nullifies_nama_mitra_and_supports_inline_new_pola(): void
    {
        $res = $this->actingAsRole('Admin')->postJson('/pesanan', [
            'pola_mode' => 'new',
            'new_pola' => [
                'id_kategori' => 1,
                'kode_pola' => '831',
                'nama_pola' => 'Atasan 831',
                'spesifikasi' => [
                    [
                        'nama_ukuran' => 'All',
                        'lingkar_dada' => 104,
                        'panjang_baju_celana' => 70,
                    ],
                ],
            ],
            'id_sumber' => 2, // Produk Abadi
            'nama_mitra' => 'Harus Dihapus Otomatis',
            'jenis_pesanan' => 'Atasan Produk Abadi 831',
            'tgl_order' => '2026-11-11',
            'deadline_awal' => '2026-11-26',
        ]);

        $res->assertCreated()->assertJson(['success' => true]);
        $orderId = $res->json('data.id_pesanan');

        $createdPola = Pola::where('kode_pola', '831')->firstOrFail();
        $this->assertDatabaseHas('Spesifikasi_Ukuran', [
            'id_pola' => $createdPola->id_pola,
            'nama_ukuran' => 'All',
        ]);

        $createdOrder = Pesanan::findOrFail($orderId);
        $this->assertNull($createdOrder->nama_mitra);
        $this->assertSame((int) $createdPola->id_pola, (int) $createdOrder->id_pola);
    }

    /**
     * BB-09: Pesanan supports update, EDF deadline revision, and status transitions.
     */
    public function test_bb09_pesanan_update_revise_deadline_and_status_transitions(): void
    {
        $upd = $this->actingAsRole('Admin')->postJson('/pesanan/2/update', [
            'id_pola' => 2,
            'id_sumber' => 1,
            'id_penerimaan' => 2,
            'jenis_pesanan' => 'Kemeja Oxford Revisi',
            'nama_mitra' => 'CV Sinar Apparel Distro',
            'deadline_awal' => '2026-11-15',
            'deadline_terbaru' => '2026-11-14',
            'status_pesanan' => 'Antrean',
        ]);
        $upd->assertOk()->assertJson(['success' => true]);

        $rev = $this->actingAsRole('Admin')->postJson('/pesanan/2/revise-deadline', [
            'deadline_terbaru' => '2026-11-12',
            'alasan_perubahan_deadline' => 'Permintaan percepatan pengiriman mitra',
        ]);
        $rev->assertOk()->assertJson(['success' => true]);

        $sts = $this->actingAsRole('Admin')->postJson('/pesanan/2/status', [
            'status_pesanan' => 'Pemotongan',
        ]);
        $sts->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('Pesanan', [
            'id_pesanan' => 2,
            'jenis_pesanan' => 'Kemeja Oxford Revisi',
            'deadline_terbaru' => '2026-11-12',
            'status_pesanan' => 'Pemotongan',
        ]);
    }

    /**
     * BB-10: Pesanan rejects invalid payload, missing revision reason, and guarded delete (negative cases).
     */
    public function test_bb10_pesanan_negative_validation_and_delete_guard(): void
    {
        $this->actingAsRole('Admin')->postJson('/pesanan', [
            'id_pola' => 9999,
            'id_sumber' => 1,
            'jenis_pesanan' => '',
        ])->assertStatus(422)->assertJsonValidationErrors(['id_pola', 'jenis_pesanan', 'deadline_awal']);

        $this->actingAsRole('Admin')->postJson('/pesanan/2/revise-deadline', [
            'deadline_terbaru' => '2026-11-10',
            'alasan_perubahan_deadline' => '',
        ])->assertStatus(422)->assertJsonValidationErrors(['alasan_perubahan_deadline']);

        // Order #1 has active Pemotongan_Seri & QC -> 422
        $this->actingAsRole('Admin')->postJson('/pesanan/1/delete')->assertStatus(422)->assertJson(['success' => false]);

        // Order #4 has no cutting/QC -> deletable (200)
        $this->actingAsRole('Admin')->postJson('/pesanan/4/delete')->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('Pesanan', ['id_pesanan' => 4]);
    }

    /**
     * BB-11: Pesanan rejects non-Admin roles with HTTP 403 (negative case).
     */
    public function test_bb11_pesanan_denies_non_admin_roles(): void
    {
        foreach (['Pemotong', 'Gudang', 'QC', 'Pemilik'] as $role) {
            $this->actingAsRole($role)->get('/pesanan')->assertStatus(403);
        }
    }

    /**
     * BB-12: Penerimaan page loads and creates supplier + multi-color multi-roll fabric receipt note.
     */
    public function test_bb12_penerimaan_index_and_multi_roll_intake_store(): void
    {
        $this->actingAsRole('Gudang')->get('/penerimaan')
            ->assertOk()
            ->assertSee('Penerimaan & Roll Bahan', false);

        $supRes = $this->actingAsRole('Gudang')->postJson('/supplier', [
            'nama_supplier' => 'PT Vista Indah Sandang',
            'kontak' => '021-5551234',
            'alamat' => 'Tangerang',
        ]);
        $supRes->assertCreated()->assertJson(['success' => true]);
        $idSupplier = $supRes->json('data.id_supplier');

        $notaRes = $this->actingAsRole('Gudang')->postJson('/penerimaan', [
            'id_supplier' => $idSupplier,
            'no_nota' => '286/11/26',
            'tgl_nota' => '2026-11-12',
            'nama_kain' => 'Baby Terry Salur 8x42',
            'lebar_gramasi' => '185 / 230-240',
            'no_kendaraan' => 'B 9725 PCI',
            'penerima' => 'Luis',
            'warna' => [
                [
                    'nama_warna' => 'Gading - Cok Kopi',
                    'kode_warna' => '2623257',
                    'satuan_ukur' => 'Kg',
                    'rolls' => [
                        ['ukuran_awal' => 25.02],
                        ['ukuran_awal' => 25.07],
                    ],
                ],
                [
                    'nama_warna' => 'Gading - Coklat',
                    'kode_warna' => '2623254',
                    'satuan_ukur' => 'Kg',
                    'rolls' => [
                        ['ukuran_awal' => 24.85],
                    ],
                ],
            ],
        ]);

        $notaRes->assertCreated()->assertJson(['success' => true]);
        $idPenerimaan = $notaRes->json('data.id_penerimaan');

        $this->assertDatabaseHas('Penerimaan_Bahan', [
            'id_penerimaan' => $idPenerimaan,
            'no_nota' => '286/11/26',
            'nama_kain' => 'Baby Terry Salur 8x42',
        ]);
        $this->assertDatabaseHas('Detail_Bahan_Warna', [
            'id_penerimaan' => $idPenerimaan,
            'nama_warna' => 'Gading - Cok Kopi',
            'total_roll' => 2,
            'total_ukuran' => 50.09,
        ]);
        $this->assertDatabaseHas('Detail_Roll_Bahan', [
            'no_roll_ke' => 1,
            'ukuran_awal' => 25.02,
            'ukuran_sisa' => 25.02,
            'status_roll' => 'Tersedia',
        ]);
    }

    /**
     * BB-13: Penerimaan supports adding a roll to an existing color group and updating roll remainder to Habis.
     */
    public function test_bb13_penerimaan_add_roll_and_update_roll_to_habis(): void
    {
        $addRes = $this->actingAsRole('Gudang')->postJson('/penerimaan/warna/2/add-roll', [
            'ukuran_awal' => 26.50,
        ]);
        $addRes->assertCreated()->assertJson(['success' => true]);
        $newRollId = $addRes->json('data.id_roll');

        $this->assertDatabaseHas('Detail_Bahan_Warna', [
            'id_detail_warna' => 2,
            'total_roll' => 4,
            'total_ukuran' => 101.50,
        ]);

        $updRes = $this->actingAsRole('Gudang')->postJson("/penerimaan/roll/{$newRollId}/update", [
            'no_roll_ke' => 4,
            'ukuran_awal' => 26.50,
            'ukuran_sisa' => 0,
            'satuan' => 'Kg',
            'status_roll' => 'Dipotong',
        ]);
        $updRes->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('Detail_Roll_Bahan', [
            'id_roll' => $newRollId,
            'ukuran_sisa' => 0.00,
            'status_roll' => 'Habis',
        ]);
    }

    /**
     * BB-14: Penerimaan rejects invalid unit, zero roll size, unknown supplier, and deletion of used roll (negative cases).
     */
    public function test_bb14_penerimaan_negative_validation_and_used_roll_delete_guard(): void
    {
        $this->actingAsRole('Gudang')->postJson('/penerimaan', [
            'id_supplier' => 9999,
            'no_nota' => 'BAD-01',
            'tgl_nota' => '2026-11-12',
            'nama_kain' => 'Test',
            'warna' => [[
                'nama_warna' => 'Merah',
                'satuan_ukur' => 'Ton',
                'rolls' => [['ukuran_awal' => 0]],
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors([
            'id_supplier',
            'warna.0.satuan_ukur',
            'warna.0.rolls.0.ukuran_awal',
        ]);

        // Roll #1 is referenced by Detail_Gelaran_Kain -> 422
        $this->actingAsRole('Gudang')->postJson('/penerimaan/roll/1/delete')
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /**
     * BB-15: Pemotongan page loads and executes series cutting transaction.
     */
    public function test_bb15_pemotongan_index_and_store_series_cutting(): void
    {
        $this->actingAsRole('Pemotong')->get('/pemotongan')
            ->assertOk()
            ->assertSee('Pemotongan Seri & Gelaran', false);

        $res = $this->actingAsRole('Pemotong')->postJson('/pemotongan', [
            'id_pesanan' => 2,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-2026-17',
            'jenis_variasi' => 'Badan Utama + Lengan + Kerah',
            'tgl_potong' => '2026-11-18 01:01:00',
            'total_lembar_hasil' => 300,
            'potong_warna' => [[
                'id_detail_warna' => 3,
                'total_lembar_direncanakan' => 300,
                'kain_terpakai' => 45.00,
                'gelaran' => [[
                    'id_roll' => 8,
                    'panjang_gelar' => 2.50,
                    'jumlah_tumpuk' => 50,
                    'total_lembar_gelar' => 300,
                    'status_roll_baru' => 'Dipotong',
                ]],
            ]],
        ]);

        $res->assertCreated()->assertJson(['success' => true]);
        $this->assertSame('75.00', (string) DetailRollBahan::findOrFail(8)->ukuran_sisa);
        $this->assertSame('Pemotongan', Pesanan::findOrFail(2)->status_pesanan);
    }

    /**
     * BB-16: Pemotongan supports updating cutting details and deleting with roll restoration.
     */
    public function test_bb16_pemotongan_update_and_delete_with_roll_restore(): void
    {
        $createRes = $this->actingAsRole('Pemotong')->postJson('/pemotongan', [
            'id_pesanan' => 2,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-UPD-01',
            'total_lembar_hasil' => 100,
            'potong_warna' => [[
                'id_detail_warna' => 3,
                'total_lembar_direncanakan' => 100,
                'kain_terpakai' => 30.00,
                'gelaran' => [[
                    'id_roll' => 8,
                    'panjang_gelar' => 2.50,
                    'jumlah_tumpuk' => 50,
                    'total_lembar_gelar' => 100,
                ]],
            ]],
        ]);
        $createRes->assertCreated();
        $idPotong = $createRes->json('data.id_potong');
        $this->assertSame('90.00', (string) DetailRollBahan::findOrFail(8)->ukuran_sisa);

        $updRes = $this->actingAsRole('Pemotong')->postJson("/pemotongan/{$idPotong}/update", [
            'id_pesanan' => 2,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-UPD-01-REV',
            'tgl_potong' => '2026-11-18 10:00:00',
            'total_lembar_hasil' => 120,
            'kain_terpakai' => 50.00,
        ]);
        $updRes->assertOk()->assertJson(['success' => true]);
        $this->assertSame('70.00', (string) DetailRollBahan::findOrFail(8)->ukuran_sisa);

        $delRes = $this->actingAsRole('Pemotong')->postJson("/pemotongan/{$idPotong}/delete");
        $delRes->assertOk()->assertJson(['success' => true]);
        $this->assertSame('120.00', (string) DetailRollBahan::findOrFail(8)->ukuran_sisa);
        $this->assertSame('Tersedia', DetailRollBahan::findOrFail(8)->status_roll);
    }

    /**
     * BB-17: Pemotongan rejects invalid payload values and unauthorized roles (negative cases).
     */
    public function test_bb17_pemotongan_negative_validation_and_role_guard(): void
    {
        $this->actingAsRole('Pemotong')->postJson('/pemotongan', [
            'id_pesanan' => 9999,
            'id_user_pemotong' => 3,
            'kode_seri' => 'SERI-INVALID',
            'total_lembar_hasil' => 0,
            'potong_warna' => [[
                'id_detail_warna' => 1,
                'total_lembar_direncanakan' => 10,
                'kain_terpakai' => 0,
                'gelaran' => [[
                    'id_roll' => 2,
                    'panjang_gelar' => 2.0,
                    'jumlah_tumpuk' => 5,
                    'total_lembar_gelar' => 10,
                ]],
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors([
            'id_pesanan',
            'total_lembar_hasil',
            'potong_warna.0.kain_terpakai',
        ]);

        $this->actingAsRole('QC')->get('/pemotongan')->assertStatus(403);
    }

    /**
     * BB-18: QC page loads and records passing Jahit Akhir inspection for Produk Abadi with automatic warehouse credit.
     */
    public function test_bb18_qc_index_and_pass_jahit_akhir_produk_abadi(): void
    {
        $this->actingAsRole('QC')->get('/qc')
            ->assertOk()
            ->assertSee('Quality Control & Kerusakan', false);

        $res = $this->actingAsRole('QC')->postJson('/qc', [
            'id_pesanan' => 3,
            'id_user_qc' => 4,
            'tahap_qc' => 'Jahit Akhir',
            'jumlah_diperiksa' => 60,
            'jumlah_lolos' => 60,
            'jumlah_cacat' => 0,
            'jenis_cacat' => 'Tidak Ada',
            'status_qc' => 'Lolos QC',
            'id_spesifikasi' => 2,
            'nama_warna' => 'Hitam Reaktif',
        ]);

        $res->assertCreated()->assertJson(['success' => true]);
        $this->assertSame(200, (int) StokBarangJadi::findOrFail(2)->jumlah_stok_pcs); // 140 + 60
        $this->assertSame('Selesai', Pesanan::findOrFail(3)->status_pesanan);
    }

    /**
     * BB-19: QC supports Need Rework inspection and rework completion workflow.
     */
    public function test_bb19_qc_need_rework_and_complete_rework_flow(): void
    {
        $createRes = $this->actingAsRole('QC')->postJson('/qc', [
            'id_pesanan' => 2,
            'id_user_qc' => 4,
            'tahap_qc' => 'Potong',
            'jumlah_diperiksa' => 100,
            'jumlah_lolos' => 92,
            'jumlah_cacat' => 8,
            'jenis_cacat' => 'Cacat Potong',
            'pic_rework' => 'Kepala Pemotong',
            'catatan_qc' => 'Perbaikan ukuran kerah',
            'status_qc' => 'Need Rework',
            'tgl_target_perbaikan' => '2026-11-14',
        ]);
        $createRes->assertCreated();
        $idQc = $createRes->json('data.id_qc');
        $this->assertSame('Dalam QC', Pesanan::findOrFail(2)->status_pesanan);

        $completeRes = $this->actingAsRole('QC')->postJson("/qc/{$idQc}/complete-rework", [
            'tgl_selesai_perbaikan' => '2026-11-13 15:00:00',
        ]);
        $completeRes->assertOk()->assertJson(['success' => true]);
        $this->assertSame('Rework Selesai', Qc::findOrFail($idQc)->status_qc);
    }

    /**
     * BB-20: QC rejects mismatched passed+defect counts, invalid stage enum, and unauthorized role (negative cases).
     */
    public function test_bb20_qc_negative_validation_and_role_guard(): void
    {
        $this->actingAsRole('QC')->postJson('/qc', [
            'id_pesanan' => 3,
            'id_user_qc' => 4,
            'tahap_qc' => 'Jahit Akhir',
            'jumlah_diperiksa' => 50,
            'jumlah_lolos' => 40,
            'jumlah_cacat' => 5,
            'jenis_cacat' => 'Cacat Jahit',
            'status_qc' => 'Need Rework',
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->actingAsRole('QC')->postJson('/qc', [
            'id_pesanan' => 3,
            'id_user_qc' => 4,
            'tahap_qc' => 'Packing',
            'jumlah_diperiksa' => 50,
            'jumlah_lolos' => 50,
            'jumlah_cacat' => 0,
            'jenis_cacat' => 'Tidak Ada',
            'status_qc' => 'Lolos QC',
        ])->assertStatus(422)->assertJsonValidationErrors(['tahap_qc']);

        $this->actingAsRole('Gudang')->get('/qc')->assertStatus(403);
    }

    /**
     * BB-21: Stok page loads and supports outbound Mitra stock adjustment, global threshold update, and ledger reversal.
     */
    public function test_bb21_stok_index_adjust_outbound_mitra_thresholds_and_delete_riwayat(): void
    {
        $this->actingAsRole('Gudang')->get('/stok')->assertOk()->assertSee('Gudang & Stok Barang Jadi', false);
        $this->actingAsRole('Pemilik')->get('/stok')->assertOk()->assertSee('Mode Audit (Read-Only)');

        $adjRes = $this->actingAsRole('Gudang')->postJson('/stok/adjust', [
            'id_pola' => 1,
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
            'delta_pcs' => -15,
            'alasan_penyesuaian' => 'Diambil Mitra (Serah Terima Request Mitra)',
            'nama_mitra' => 'Jupiter',
            'keterangan' => 'SJ-BB21-01',
        ]);
        $adjRes->assertOk()->assertJson(['success' => true]);
        $this->assertSame(65, (int) StokBarangJadi::findOrFail(1)->jumlah_stok_pcs);

        $thrRes = $this->actingAsRole('Gudang')->postJson('/stok/thresholds', [
            'min_stok_jadi' => 25,
            'min_stok_aksesoris' => 600,
        ]);
        $thrRes->assertOk()->assertJson(['success' => true]);

        $latest = RiwayatStokBarangJadi::orderBy('id_riwayat_stok', 'desc')->firstOrFail();
        $revRes = $this->actingAsRole('Gudang')->postJson("/stok/riwayat/{$latest->id_riwayat_stok}/delete");
        $revRes->assertOk()->assertJson(['success' => true]);
        $this->assertSame(80, (int) StokBarangJadi::findOrFail(1)->jumlah_stok_pcs);
    }

    /**
     * BB-22: Stok rejects negative stock underflow, missing Mitra name, and invalid thresholds (negative cases).
     */
    public function test_bb22_stok_negative_underflow_missing_mitra_and_invalid_threshold(): void
    {
        $this->actingAsRole('Gudang')->postJson('/stok/adjust', [
            'id_pola' => 1,
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
            'delta_pcs' => -999,
            'alasan_penyesuaian' => 'Penjualan Langsung',
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->actingAsRole('Gudang')->postJson('/stok/adjust', [
            'id_pola' => 1,
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
            'delta_pcs' => -5,
            'alasan_penyesuaian' => 'Diambil Mitra',
            'nama_mitra' => '',
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->actingAsRole('Gudang')->postJson('/stok/thresholds', [
            'min_stok_jadi' => -10,
            'min_stok_aksesoris' => -5,
        ])->assertStatus(422);
    }

    /**
     * BB-23: Stok blocks Read-Only Pemilik mutations with HTTP 403 (negative case).
     */
    public function test_bb23_stok_blocks_pemilik_read_only_mutations(): void
    {
        $this->actingAsRole('Pemilik')->postJson('/stok/adjust', [
            'id_pola' => 1,
            'id_spesifikasi' => 1,
            'nama_warna' => 'Hitam Reaktif',
            'delta_pcs' => 5,
        ])->assertStatus(403);

        $this->actingAsRole('Pemilik')->postJson('/stok/thresholds', [
            'min_stok_jadi' => 10,
            'min_stok_aksesoris' => 100,
        ])->assertStatus(403);
    }

    /**
     * BB-24: Laporan renders all 5 modes, filters by date/source, and exposes CSV & Word (.doc) export payloads.
     */
    public function test_bb24_laporan_renders_all_five_modes_filters_and_csv_doc_export_payloads(): void
    {
        $modes = [
            'KONSOLIDASI' => 'Laporan Rekapitulasi &amp; Konsolidasi Produksi CMT',
            'STOK' => 'Laporan Posisi Stok Gudang Barang Jadi',
            'BAHAN_MASUK' => 'Laporan Penerimaan Bahan Baku',
            'MUTASI_KELUAR' => 'Laporan Pengeluaran Barang Jadi',
            'QC' => 'Laporan Rekapitulasi Inspeksi Quality Control',
        ];

        foreach ($modes as $mode => $headingSnippet) {
            $htmlRes = $this->actingAsRole('Admin')->get("/laporan?jenis_laporan={$mode}");
            $htmlRes->assertOk()
                ->assertSee($headingSnippet, false)
                ->assertSee('exportLaporanExcel()', false)
                ->assertSee('exportLaporanWord()', false)
                ->assertSee('const exportDataRows =', false)
                ->assertSee('const wordMeta =', false)
                ->assertSee('id="laporan-dokumen-resmi"', false)
                ->assertSee('class="doc-table', false);
        }

        // Pemilik has read-only access to /laporan
        $this->actingAsRole('Pemilik')->get('/laporan?jenis_laporan=KONSOLIDASI')
            ->assertOk()
            ->assertSee('exportLaporanExcel()', false)
            ->assertSee('exportLaporanWord()', false);

        // Date and Sumber_Inisiasi filter verification
        $filtered = $this->actingAsRole('Admin')->getJson('/laporan?jenis_laporan=KONSOLIDASI&start_date=2026-10-20&end_date=2026-10-23&id_sumber=1');
        $filtered->assertOk()->assertJson(['success' => true]);
        $filteredOrderIds = array_column($filtered->json('data.consolidatedOrders'), 'id_pesanan');
        $this->assertSame([1, 2], $filteredOrderIds);
    }

    /**
     * BB-25: Laporan updates Kop Surat company profile and reflects changes in rendered HTML and Word export metadata.
     */
    public function test_bb25_laporan_update_company_profile_kop_surat(): void
    {
        $res = $this->actingAsRole('Admin')->postJson('/laporan/profile', [
            'nama_perusahaan' => 'BONG GARMENT INDONESIA',
            'tagline' => 'MANUFAKTUR CMT TERPADU',
            'alamat' => 'Jl. Industri Tekstil No. 99, Jakarta',
            'telepon' => '(021) 8899-7766',
            'deskripsi_sistem' => 'Sistem Produksi & Inventori CMT Terintegrasi',
            'kota_pengesahan' => 'Jakarta Barat',
        ]);

        $res->assertOk()->assertJson([
            'success' => true,
            'data' => [
                'nama_perusahaan' => 'BONG GARMENT INDONESIA',
                'kota_pengesahan' => 'Jakarta Barat',
            ],
        ]);

        $this->actingAsRole('Admin')->get('/laporan')
            ->assertOk()
            ->assertSee('BONG GARMENT INDONESIA')
            ->assertSee('Jl. Industri Tekstil No. 99, Jakarta');
    }

    /**
     * BB-26: Laporan handles invalid report type fallback, profile validation errors, and RBAC blocks (negative cases).
     */
    public function test_bb26_laporan_negative_invalid_mode_validation_and_rbac(): void
    {
        $this->actingAsRole('Admin')->get('/laporan?jenis_laporan=TIDAK_VALID')
            ->assertOk()
            ->assertSee('Laporan Rekapitulasi &amp; Konsolidasi Produksi CMT', false);

        $this->actingAsRole('Admin')->postJson('/laporan/profile', [
            'nama_perusahaan' => '',
            'tagline' => 'Tag',
            'alamat' => 'Alamat',
            'telepon' => '123',
            'deskripsi_sistem' => 'Desc',
            'kota_pengesahan' => 'Jakarta',
        ])->assertStatus(422)->assertJsonValidationErrors(['nama_perusahaan']);

        $this->actingAsRole('Pemilik')->postJson('/laporan/profile', [
            'nama_perusahaan' => 'Blocked',
            'tagline' => 'Tag',
            'alamat' => 'Alamat',
            'telepon' => '123',
            'deskripsi_sistem' => 'Desc',
            'kota_pengesahan' => 'Jakarta',
        ])->assertStatus(403);

        $this->actingAsRole('Pemotong')->get('/laporan')->assertStatus(403);
    }

    /**
     * BB-27: Pengaturan renders dashboard and executes User CRUD lifecycle including login verification.
     */
    public function test_bb27_pengaturan_index_and_user_crud_lifecycle(): void
    {
        $this->actingAsRole('Admin')->get('/pengaturan')
            ->assertOk()
            ->assertSee('Pengaturan Sistem & Manajemen Pengguna', false);

        $createRes = $this->actingAsRole('Admin')->postJson('/pengaturan/user', [
            'nama_user' => 'Operator Potong Baru',
            'username' => 'cutter_baru',
            'password' => 'rahasia123',
            'role' => 'Pemotong',
        ]);
        $createRes->assertCreated()->assertJson(['success' => true]);
        $newUserId = $createRes->json('data.id_user');

        // Verify newly created account can log in
        $this->actingAsGuest()->postJson('/login', [
            'username' => 'cutter_baru',
            'password' => 'rahasia123',
        ])->assertOk()->assertJson(['success' => true, 'redirect' => '/pemotongan']);

        $updRes = $this->actingAsRole('Admin')->postJson("/pengaturan/user/{$newUserId}/update", [
            'nama_user' => 'Supervisor Gudang Baru',
            'username' => 'cutter_baru',
            'password' => 'baru456',
            'role' => 'Gudang',
        ]);
        $updRes->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('User', [
            'id_user' => $newUserId,
            'nama_user' => 'Supervisor Gudang Baru',
            'role' => 'Gudang',
        ]);

        $delRes = $this->actingAsRole('Admin')->postJson("/pengaturan/user/{$newUserId}/delete");
        $delRes->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('User', ['id_user' => $newUserId]);
    }

    /**
     * BB-28: Pengaturan manages master lookups, wipes operational data while preserving users, and reloads sample data.
     */
    public function test_bb28_pengaturan_master_lookup_reset_operational_and_seed_sample(): void
    {
        $this->actingAsRole('Admin')->postJson('/pengaturan/kategori', [
            'nama_kategori' => 'Atasan Rajut',
        ])->assertCreated()->assertJson(['success' => true]);

        $this->actingAsRole('Admin')->postJson('/pengaturan/sumber', [
            'nama_sumber' => 'Pre-Order Komunitas',
        ])->assertCreated()->assertJson(['success' => true]);

        $userCountBefore = User::count();

        $resetRes = $this->actingAsRole('Admin')->postJson('/pengaturan/reset-operational', [
            'include_master' => false,
        ]);
        $resetRes->assertOk()->assertJson(['success' => true]);

        $this->assertSame(0, Pesanan::count());
        $this->assertSame(0, DB::table('Penerimaan_Bahan')->count());
        $this->assertSame(0, DetailRollBahan::count());
        $this->assertSame(0, StokBarangJadi::count());
        $this->assertSame($userCountBefore, User::count());
        $this->assertGreaterThan(0, Pola::count());

        $seedRes = $this->actingAsRole('Admin')->postJson('/pengaturan/seed-sample');
        $seedRes->assertOk()->assertJson(['success' => true]);
        $this->assertGreaterThan(0, Pesanan::count());
        $this->assertGreaterThan(0, DetailRollBahan::count());
        $this->assertSame($userCountBefore, User::count());
    }

    /**
     * BB-29: Pengaturan enforces duplicate username, invalid role, self-deletion, last-Admin deletion, and non-Admin blocks (negative cases).
     */
    public function test_bb29_pengaturan_negative_validation_self_delete_last_admin_and_rbac(): void
    {
        $this->actingAsRole('Admin')->postJson('/pengaturan/user', [
            'nama_user' => 'Duplicate',
            'username' => 'admin',
            'password' => 'pass123',
            'role' => 'SuperAdmin',
        ])->assertStatus(422)->assertJsonValidationErrors(['username', 'role']);

        // Cannot delete currently logged-in user (id_user = 1)
        $this->actingAsRole('Admin', 'Admin', 1, 'Administrator')
            ->postJson('/pengaturan/user/1/delete')
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        // Cannot delete the sole remaining Admin account even from another user id
        $this->actingAsRole('Admin', 'Admin', 999, 'External Admin')
            ->postJson('/pengaturan/user/1/delete')
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        foreach (['Pemilik', 'Gudang', 'Pemotong', 'QC'] as $role) {
            $this->actingAsRole($role)->get('/pengaturan')->assertStatus(403);
        }
    }
}
