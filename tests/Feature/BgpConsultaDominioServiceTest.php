<?php

namespace Tests\Feature;

use App\Services\BgpConsultaRedeService;
use App\Services\BgpResolucaoDominioService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class BgpConsultaDominioServiceTest extends TestCase
{
    private function dns(array $registros): BgpResolucaoDominioService
    {
        return new BgpResolucaoDominioService(
            fn (string $dominio, int $tipos): array => $registros
        );
    }

    public function test_consulta_dominio_ipv4_e_ipv6(): void
    {
        $dns = $this->dns([
            ['type' => 'A', 'ip' => '8.8.8.8'],
            [
                'type' => 'AAAA',
                'ipv6' => '2606:4700:4700::1111',
            ],
        ]);

        Http::fake(function (Request $request) {
            parse_str(
                (string) parse_url($request->url(), PHP_URL_QUERY),
                $parametros
            );

            $ip = $parametros['resource'] ?? '';

            $dados = match ($ip) {
                '8.8.8.8' => [
                    'prefix' => '8.8.8.0/24',
                    'asns' => ['15169'],
                ],
                '2606:4700:4700::1111' => [
                    'prefix' => '2606:4700:4700::/48',
                    'asns' => ['13335'],
                ],
                default => [],
            };

            return Http::response([
                'status' => 'ok',
                'data' => $dados,
            ], 200);
        });

        $resultado = (new BgpConsultaRedeService($dns))
            ->consultar('Example.COM');

        $this->assertSame('dominio', $resultado['tipo']);
        $this->assertSame('example.com', $resultado['consulta']);
        $this->assertCount(2, $resultado['resultados']);

        $this->assertSame(
            [15169],
            $resultado['resultados'][0]['asns']
        );

        $this->assertSame(
            [13335],
            $resultado['resultados'][1]['asns']
        );

        Http::assertSentCount(2);
    }

    public function test_nao_consulta_ip_duplicado(): void
    {
        $dns = $this->dns([
            ['type' => 'A', 'ip' => '8.8.8.8'],
            ['type' => 'A', 'ip' => '8.8.8.8'],
        ]);

        Http::fake([
            '*' => Http::response([
                'status' => 'ok',
                'data' => [
                    'prefix' => '8.8.8.0/24',
                    'asns' => ['15169'],
                ],
            ], 200),
        ]);

        $resultado = (new BgpConsultaRedeService($dns))
            ->consultar('example.com');

        $this->assertCount(1, $resultado['resultados']);

        Http::assertSentCount(1);
    }

    public function test_rejeita_dns_com_ip_privado(): void
    {
        $dnsConsultado = false;

        $dns = new BgpResolucaoDominioService(
            function (string $dominio, int $tipos) use (&$dnsConsultado): array {
                $dnsConsultado = true;

                return [
                    ['type' => 'A', 'ip' => '8.8.8.8'],
                    ['type' => 'A', 'ip' => '192.168.1.10'],
                ];
            }
        );

        Http::fake();

        try {
            (new BgpConsultaRedeService($dns))
                ->consultar('example.com');

            $this->fail('Deveria rejeitar o IP privado.');
        } catch (InvalidArgumentException $erro) {
            $this->assertTrue(
                $dnsConsultado,
                'O resolvedor DNS deveria ter sido executado.'
            );

            Http::assertNothingSent();
        }
    }

    public function test_dominio_sem_ips_retorna_erro(): void
    {
        $dns = $this->dns([]);

        Http::fake();

        $this->expectException(RuntimeException::class);

        (new BgpConsultaRedeService($dns))
            ->consultar('example.com');
    }

    public function test_falha_ripestat_retorna_erro(): void
    {
        $dns = $this->dns([
            ['type' => 'A', 'ip' => '8.8.8.8'],
        ]);

        Http::fake([
            '*' => Http::response([], 503),
        ]);

        $this->expectException(RuntimeException::class);

        (new BgpConsultaRedeService($dns))
            ->consultar('example.com');
    }

    public function test_limita_consultas_a_oito_ips(): void
    {
        $registros = [];

        for ($i = 1; $i <= 10; $i++) {
            $registros[] = [
                'type' => 'A',
                'ip' => '8.8.8.' . $i,
            ];
        }

        $dns = $this->dns($registros);

        Http::fake([
            '*' => Http::response([
                'status' => 'ok',
                'data' => [
                    'prefix' => '8.8.8.0/24',
                    'asns' => ['15169'],
                ],
            ], 200),
        ]);

        $resultado = (new BgpConsultaRedeService($dns))
            ->consultar('example.com');

        $this->assertCount(8, $resultado['resultados']);

        Http::assertSentCount(8);
    }
}
