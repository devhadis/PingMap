import test from 'node:test';
import assert from 'node:assert/strict';

import {
    prepararTrechosMapa
} from '../../public/js/mapa-linhas.js';

test('liga saltos consecutivos', () => {
    const pontos = [
        { salto: 6, latitude: -23, longitude: -46 },
        { salto: 7, latitude: -22, longitude: -43 }
    ];

    assert.deepEqual(prepararTrechosMapa(pontos), [
        [[-23, -46], [-22, -43]]
    ]);
});

test('nao liga saltos com lacunas', () => {
    const pontos = [
        { salto: 2, latitude: -23, longitude: -46 },
        { salto: 5, latitude: -22, longitude: -43 }
    ];

    assert.deepEqual(prepararTrechosMapa(pontos), []);
});

test('ignora segmentos com coordenadas iguais', () => {
    const pontos = [
        { salto: 7, latitude: -22, longitude: -43 },
        { salto: 8, latitude: -22, longitude: -43 }
    ];

    assert.deepEqual(prepararTrechosMapa(pontos), []);
});

test('ignora coordenadas invalidas', () => {
    const pontos = [
        { salto: 1, latitude: -23, longitude: -46 },
        { salto: 2, latitude: 999, longitude: -43 }
    ];

    assert.deepEqual(prepararTrechosMapa(pontos), []);
});

test('aceita lista vazia', () => {
    assert.deepEqual(prepararTrechosMapa([]), []);
});
