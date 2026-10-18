<?php

namespace Tests\Feature;

use App\Jobs\ExecutarConsultaDns;
use App\Models\User;
use App\Services\DnsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnsIpv6JobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_salva_registro_aaaa(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->dnsConsultas()->create([
            'dominio' => 'cloudflare.com',
            'tipo' => 'AAAA',
            'status' => 'pendente',
        ]);

        $dns = new DnsService(
            resolvedor: function ($dominio, $tipo) {
                $this->assertSame('cloudflare.com', $dominio);
                $this->assertSame(DNS_AAAA, $tipo);

                return [[
                    'type' => 'AAAA',
                    'ipv6' => '2606:4700::1111',
                ]];
            }
        );

        (new ExecutarConsultaDns($consulta->id))->handle($dns);

        $consulta->refresh();

        $this->assertSame('sucesso', $consulta->status);
        $this->assertSame(1, $consulta->quantidade);
        $this->assertSame(
            '2606:4700::1111',
            $consulta->registros[0]['ipv6']
        );
    }

    public function test_job_salva_ptr_ipv6(): void
    {
        $usuario = User::factory()->create();
        $ip = '2606:4700:4700::1111';

        $consulta = $usuario->dnsConsultas()->create([
            'dominio' => $ip,
            'tipo' => 'PTR',
            'status' => 'pendente',
        ]);

        $dns = new DnsService(
            resolvedor: function ($dominio, $tipo) use ($ip) {
                $esperado = implode(
                    '.',
                    array_reverse(
                        str_split(bin2hex(inet_pton($ip)))
                    )
                ) . '.ip6.arpa';

                $this->assertSame($esperado, $dominio);
                $this->assertSame(DNS_PTR, $tipo);

                return [[
                    'type' => 'PTR',
                    'target' => 'one.one.one.one',
                ]];
            }
        );

        (new ExecutarConsultaDns($consulta->id))->handle($dns);

        $consulta->refresh();

        $this->assertSame('sucesso', $consulta->status);
        $this->assertSame(1, $consulta->quantidade);
        $this->assertSame(
            'one.one.one.one',
            $consulta->registros[0]['target']
        );
        $this->assertSame($ip, $consulta->dominio);
    }
}
