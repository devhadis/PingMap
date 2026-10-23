<?php

namespace Tests\Unit;

use App\Services\RdapOpcoesConexaoSegura;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RdapMultiplosIpsTest extends TestCase
{
    #[Test]
    public function preserva_multiplos_ipv4(): void
    {
        $opcoes = (new RdapOpcoesConexaoSegura())->preparar(
            'rdap.arin.net',
            ['8.8.8.8', '1.1.1.1']
        );

        $this->assertSame(
            ['rdap.arin.net:443:8.8.8.8,1.1.1.1'],
            $opcoes['curl'][CURLOPT_RESOLVE]
        );
    }

    #[Test]
    public function preserva_ipv4_e_ipv6(): void
    {
        $opcoes = (new RdapOpcoesConexaoSegura())->preparar(
            'rdap.arin.net',
            ['8.8.8.8', '2606:4700:4700::1111']
        );

        $this->assertSame(
            ['rdap.arin.net:443:8.8.8.8,[2606:4700:4700::1111]'],
            $opcoes['curl'][CURLOPT_RESOLVE]
        );
    }
}
