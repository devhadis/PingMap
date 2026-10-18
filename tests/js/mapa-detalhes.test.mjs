import test from 'node:test';
import assert from 'node:assert/strict';

import {
    prepararDetalhesSalto
} from '../../public/js/mapa-detalhes.js';

test('prepara os detalhes completos do salto', () => {
    const salto = {
        salto: 7,
        ip: '200.255.177.54',
        latencia_ms: 98.973,
        geo: {
            cidade: 'Rio de Janeiro',
            pais: 'Brazil',
            asn: 4230,
            organizacao: 'CLARO S.A.'
        }
    };

    assert.deepEqual(prepararDetalhesSalto(salto), {
        salto: 7,
        ip: '200.255.177.54',
        cidade: 'Rio de Janeiro',
        pais: 'Brazil',
        asn: 'AS4230',
        organizacao: 'CLARO S.A.',
        latencia: '98.973 ms'
    });
});

test('trata dados ausentes', () => {
    const detalhes = prepararDetalhesSalto({
        salto: 3,
        ip: null,
        geo: null
    });

    assert.equal(detalhes.ip, 'Não disponível');
    assert.equal(detalhes.cidade, 'Não disponível');
    assert.equal(detalhes.latencia, 'Não disponível');
});

test('preserva texto sem interpretar HTML', () => {
    const detalhes = prepararDetalhesSalto({
        salto: 1,
        ip: '1.1.1.1',
        geo: {
            cidade: '<script>alert(1)</script>'
        }
    });

    assert.equal(
        detalhes.cidade,
        '<script>alert(1)</script>'
    );
});
