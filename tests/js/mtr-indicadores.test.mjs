import test from 'node:test';
import assert from 'node:assert/strict';

import {
    calcularIndicadoresMtr
} from '../../public/js/mtr-indicadores.js';

test('destino alcançado com respostas válidas', () => {
    const resultado = calcularIndicadoresMtr({
        destino: '1.1.1.1',
        saltos: [
            {
                salto: 1,
                ip: '192.168.1.1',
                enviados: 3,
                perda_pct: 0,
                media_ms: 2.5
            },
            {
                salto: 2,
                ip: '1.1.1.1',
                enviados: 3,
                perda_pct: 0,
                media_ms: 42.5
            }
        ]
    });

    assert.equal(resultado.destinoAlcancado, true);
    assert.equal(resultado.perdaDestinoPct, 0);
    assert.equal(resultado.latenciaMediaDestinoMs, 42.5);
    assert.equal(resultado.totalSaltos, 2);
});

test('salto intermediário sem resposta não invalida destino', () => {
    const resultado = calcularIndicadoresMtr({
        destino: '1.1.1.1',
        saltos: [
            {
                salto: 1,
                ip: null,
                enviados: 3,
                perda_pct: 100,
                media_ms: null
            },
            {
                salto: 2,
                ip: '1.1.1.1',
                enviados: 3,
                perda_pct: 0,
                media_ms: 50
            }
        ]
    });

    assert.equal(resultado.destinoAlcancado, true);
    assert.equal(resultado.perdaDestinoPct, 0);
    assert.equal(resultado.totalSaltos, 2);
});

test('destino ausente não é confirmado', () => {
    const resultado = calcularIndicadoresMtr({
        destino: '1.1.1.1',
        saltos: [
            {
                salto: 1,
                ip: '192.168.1.1',
                enviados: 3,
                perda_pct: 0,
                media_ms: 2
            }
        ]
    });

    assert.equal(resultado.destinoAlcancado, false);
    assert.equal(resultado.perdaDestinoPct, null);
    assert.equal(resultado.latenciaMediaDestinoMs, null);
});

test('destino com 100 por cento sem resposta não é confirmado', () => {
    const resultado = calcularIndicadoresMtr({
        destino: '1.1.1.1',
        saltos: [
            {
                salto: 1,
                ip: '1.1.1.1',
                enviados: 3,
                perda_pct: 100,
                media_ms: null
            }
        ]
    });

    assert.equal(resultado.destinoAlcancado, false);
    assert.equal(resultado.perdaDestinoPct, 100);
    assert.equal(resultado.latenciaMediaDestinoMs, null);
});

test('valores inválidos não geram métricas falsas', () => {
    const resultado = calcularIndicadoresMtr({
        destino: '1.1.1.1',
        saltos: [
            {
                salto: 1,
                ip: '1.1.1.1',
                enviados: 3,
                perda_pct: -10,
                media_ms: 'invalido'
            }
        ]
    });

    assert.equal(resultado.destinoAlcancado, false);
    assert.equal(resultado.perdaDestinoPct, null);
    assert.equal(resultado.latenciaMediaDestinoMs, null);
});

test('lista vazia retorna indicadores neutros', () => {
    const resultado = calcularIndicadoresMtr({
        destino: '1.1.1.1',
        saltos: []
    });

    assert.equal(resultado.destinoAlcancado, false);
    assert.equal(resultado.perdaDestinoPct, null);
    assert.equal(resultado.latenciaMediaDestinoMs, null);
    assert.equal(resultado.totalSaltos, 0);
});
