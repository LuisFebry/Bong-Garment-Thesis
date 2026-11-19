<?php

namespace Tests;

use Database\Seeders\BongGarment18TableSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected static bool $schemaMigrated = false;

    private bool $thresholdsExisted = false;
    private ?string $thresholdsBackup = null;

    private bool $profileExisted = false;
    private ?string $profileBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $_COOKIE = [];

        $dbName = DB::connection()->getDatabaseName();
        $this->assertSame(
            'bong_garment_testing',
            $dbName,
            "Refusing to run tests against non-isolated database '{$dbName}'."
        );

        $this->backupStorageFiles();

        if (!self::$schemaMigrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            self::$schemaMigrated = true;
        }

        $this->seed(BongGarment18TableSeeder::class);
    }

    protected function tearDown(): void
    {
        $this->restoreStorageFiles();
        $_COOKIE = [];

        parent::tearDown();
    }

    protected function actingAsRole(
        string $authRole,
        ?string $activeRole = null,
        ?int $userId = null,
        ?string $userName = null
    ): static {
        $defaults = [
            'Admin' => [1, 'Administrator'],
            'Pemilik' => [2, 'Bong (Pemilik CMT)'],
            'Pemotong' => [3, 'Budi Santoso (Head Cutter)'],
            'QC' => [4, 'Siti Rahma (Lead Quality Control)'],
            'Gudang' => [5, 'Agus Hermawan (Kepala Gudang & Inventori)'],
        ];

        [$defaultId, $defaultName] = $defaults[$authRole] ?? [1, 'Administrator'];
        $activeRole ??= $authRole;
        $userId ??= $defaultId;
        $userName ??= $defaultName;

        return $this->withCredentials()->withUnencryptedCookies([
            'bong_garment_auth_role' => $authRole,
            'bong_garment_role' => $activeRole,
            'bong_garment_user_id' => (string) $userId,
            'bong_garment_user_name' => $userName,
        ]);
    }

    protected function actingAsGuest(): static
    {
        $this->withCredentials = false;
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];
        $_COOKIE = [];

        return $this;
    }

    private function backupStorageFiles(): void
    {
        $thresholdsPath = storage_path('app/stock_thresholds.json');
        $this->thresholdsExisted = is_file($thresholdsPath);
        $this->thresholdsBackup = $this->thresholdsExisted ? (string) file_get_contents($thresholdsPath) : null;

        $profilePath = storage_path('app/company_profile.json');
        $this->profileExisted = is_file($profilePath);
        $this->profileBackup = $this->profileExisted ? (string) file_get_contents($profilePath) : null;
    }

    private function restoreStorageFiles(): void
    {
        $thresholdsPath = storage_path('app/stock_thresholds.json');
        if ($this->thresholdsExisted && $this->thresholdsBackup !== null) {
            file_put_contents($thresholdsPath, $this->thresholdsBackup);
        } elseif (is_file($thresholdsPath)) {
            @unlink($thresholdsPath);
        }

        $profilePath = storage_path('app/company_profile.json');
        if ($this->profileExisted && $this->profileBackup !== null) {
            file_put_contents($profilePath, $this->profileBackup);
        } elseif (is_file($profilePath)) {
            @unlink($profilePath);
        }
    }
}
