import test from 'node:test';
import assert from 'node:assert/strict';

import {
    prepararPontosMapa
} from '../../public/js/mapa-saltos.js';

test('converte saltos geolocalizados em pontos', () => {
    const saltos = [
        {
            salto: 1,
            ip: '8.8.8.8',
            geo: {
                latitude: -22.9,
                longitude: -43.2,
                cidade: 'Rio de Janeiro',
                pais: 'Brasil'
            }
        }
    ];

    const pontos = prepararPontosMapa(saltos);

    assert.equal(pontos.length, 1);
    assert.equal(pontos[0].salto, 1);
    assert.equal(pontos[0].ip, '8.8.8.8');
    assert.equal(pontos[0].latitude, -22.9);
    assert.equal(pontos[0].longitude, -43.2);
});

test('ignora saltos sem geolocalizacao', () => {
    const pontos = prepararPontosMapa([
        { salto: 1, ip: null, geo: null },
        { salto: 2, ip: '1.1.1.1' }
    ]);

    assert.deepEqual(pontos, []);
});

test('rejeita coordenadas invalidas', () => {
    const pontos = prepararPontosMapa([
        { salto: 1, ip: '1.1.1.1', geo: {
            latitude: 95, longitude: 10
        }},
        { salto: 2, ip: '8.8.8.8', geo: {
            latitude: 10, longitude: -190
        }},
        { salto: 3, ip: '9.9.9.9', geo: {
            latitude: 'abc', longitude: 20
        }}
    ]);

    assert.deepEqual(pontos, []);
});

test('preserva a ordem original dos saltos', () => {
    const pontos = prepararPontosMapa([
        { salto: 2, ip: '1.1.1.1', geo: {
            latitude: 10, longitude: 20
        }},
        { salto: 5, ip: '8.8.8.8', geo: {
            latitude: 30, longitude: 40
        }}
    ]);

    assert.deepEqual(
        pontos.map(p => p.salto),
        [2, 5]
    );
});

test('aceita IPv6 como identificador de salto', () => {
    const pontos = prepararPontosMapa([
        {
            salto: 1,
            ip: '2606:4700:4700::1111',
            geo: {
                latitude: 10,
                longitude: 20
            }
        }
    ]);

    assert.equal(
        pontos[0].ip,
        '2606:4700:4700::1111'
    );
});

test('rejeita entrada que nao seja lista', () => {
    assert.deepEqual(prepararPontosMapa(null), []);
    assert.deepEqual(prepararPontosMapa({}), []);
});

test('preserva dados necessários para o popup', () => {
    const pontos = prepararPontosMapa([{
        salto: 7,
        ip: '1.1.1.1',
        latencia_ms: 12.5,
        geo: {
            latitude: -22.9,
            longitude: -43.2,
            cidade: 'Rio de Janeiro',
            pais: 'Brasil',
            asn: 13335,
            organizacao: 'Exemplo'
        }
    }]);

    assert.equal(pontos[0].latencia_ms, 12.5);
    assert.equal(pontos[0].geo.asn, 13335);
    assert.equal(pontos[0].geo.organizacao, 'Exemplo');
});

test('utiliza latência média do MTR quando disponível', () => {
    const pontos = prepararPontosMapa([{
        salto: 8,
        ip: '1.1.1.1',
        media_ms: 23.456,
        geo: {
            latitude: -23,
            longitude: -43
        }
    }]);

    assert.equal(pontos[0].latencia_ms, 23.456);
});
