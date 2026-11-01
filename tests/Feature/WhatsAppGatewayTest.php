<?php

namespace Tests\Feature;

use App\Services\WhatsApp\FonnteGateway;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppGatewayTest extends TestCase
{
    public function test_fonnte_berhasil_memasukkan_pesan_ke_antrean(): void
    {
        config(['whatsapp.fonnte.token' => 'token-uji']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true, 'detail' => 'success! message in queue'])]);

        $result = (new FonnteGateway())->send('085720000001', 'halo');

        $this->assertTrue($result->ok);
        $this->assertFalse($result->simulated);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'token-uji')
            && $request['target'] === '085720000001'
            && $request['message'] === 'halo');
    }

    public function test_fonnte_gagal_membawa_alasan_dari_fonnte(): void
    {
        config(['whatsapp.fonnte.token' => 'token-uji']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'token invalid'])]);

        $result = (new FonnteGateway())->send('085720000001', 'halo');

        $this->assertFalse($result->ok);
        $this->assertSame('token invalid', $result->reason);
    }

    public function test_fonnte_menerima_kunci_status_berhuruf_besar(): void
    {
        config(['whatsapp.fonnte.token' => 'token-uji']);
        Http::fake(['api.fonnte.com/*' => Http::response(['Status' => false, 'reason' => 'target invalid'])]);

        $this->assertFalse((new FonnteGateway())->send('abc', 'halo')->ok);
    }

    public function test_tanpa_token_tidak_ada_permintaan_yang_dikirim(): void
    {
        config(['whatsapp.fonnte.token' => null]);
        Http::fake();

        $result = (new FonnteGateway())->send('085720000001', 'halo');

        $this->assertFalse($result->ok);
        Http::assertNothingSent();
    }
}
