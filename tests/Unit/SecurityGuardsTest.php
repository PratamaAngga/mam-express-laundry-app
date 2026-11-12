<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Pengaman regresi: pernah terjadi kode keamanan (pembatasan login, proteksi route) hilang tanpa sengaja
 * saat sebuah file ditulis ulang. Tes ini membaca kode sumbernya dan gagal kalau pengamannya lenyap lagi.
 */
class SecurityGuardsTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/' . $path);
    }

    public function test_login_dibatasi_percobaannya(): void
    {
        $login = $this->source('app/Http/Controllers/AuthController.php');

        $this->assertStringContainsString('RateLimiter::tooManyAttempts', $login);
        $this->assertStringContainsString('RateLimiter::hit', $login);
        $this->assertStringContainsString('RateLimiter::clear', $login);
    }

    public function test_akun_nonaktif_langsung_dikeluarkan_dari_sesi(): void
    {
        $middleware = $this->source('app/Http/Middleware/RoleMiddleware.php');

        $this->assertStringContainsString('status_aktif', $middleware);
        $this->assertStringContainsString('Auth::logout()', $middleware);
    }

    public function test_setiap_grup_route_peran_dilindungi_login_dan_role(): void
    {
        $routes = $this->source('routes/web.php');

        foreach (['owner' => 'owner_admin', 'cashier' => 'kasir', 'processing' => 'petugas_processing', 'supplier' => 'supplier'] as $prefix => $role) {
            $this->assertMatchesRegularExpression(
                "/Route::middleware\(\['auth', 'role:{$role}'\]\)->prefix\('{$prefix}'\)/",
                $routes,
                "Grup route /{$prefix} harus memakai middleware ['auth', 'role:{$role}']"
            );
        }
    }

    public function test_restock_baru_hanya_untuk_supplier_aktif(): void
    {
        $restock = $this->source('app/Http/Controllers/Owner/RestockController.php');

        $this->assertStringContainsString('is_active', $restock);
    }
}
