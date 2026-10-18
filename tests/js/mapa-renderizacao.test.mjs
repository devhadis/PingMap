import test from 'node:test';
import assert from 'node:assert/strict';

import {
    criarRenderizadorMapa
} from '../../public/js/mapa-renderizacao.js';

function criarLeafletFalso() {
    const estado = {
        marcadores: [],
        removidos: 0,
        limites: null
    };

    const grupo = {
        clearLayers() {
            estado.removidos += 1;
            estado.marcadores = [];
        },
        addTo() {
            return this;
        }
    };

    const leaflet = {
        layerGroup() {
            return grupo;
        },
        circleMarker(coordenadas) {
            return {
                addTo() {
                    estado.marcadores.push(coordenadas);
                    return this;
                }
            };
        },
        latLngBounds(coordenadas) {
            return coordenadas;
        }
    };

    const mapa = {
        fitBounds(limites) {
            estado.limites = limites;
        },
        setView() {}
    };

    return { leaflet, mapa, estado };
}

test('desenha um marcador por ponto valido', () => {
    const { leaflet, mapa, estado } = criarLeafletFalso();
    const renderizador = criarRenderizadorMapa(leaflet, mapa);

    renderizador.desenhar([
        { latitude: -22.9, longitude: -43.2, ip: '1.1.1.1' },
        { latitude: 40.7, longitude: -74.0, ip: '8.8.8.8' }
    ]);

    assert.deepEqual(estado.marcadores, [
        [-22.9, -43.2],
        [40.7, -74.0]
    ]);
});

test('ignora coordenadas invalidas', () => {
    const { leaflet, mapa, estado } = criarLeafletFalso();
    const renderizador = criarRenderizadorMapa(leaflet, mapa);

    renderizador.desenhar([
        { latitude: 200, longitude: 10 },
        { latitude: 0, longitude: 0 }
    ]);

    assert.deepEqual(estado.marcadores, [[0, 0]]);
});

test('limpa marcadores anteriores ao trocar consulta', () => {
    const { leaflet, mapa, estado } = criarLeafletFalso();
    const renderizador = criarRenderizadorMapa(leaflet, mapa);

    renderizador.desenhar([
        { latitude: 10, longitude: 20 }
    ]);

    renderizador.desenhar([
        { latitude: 30, longitude: 40 }
    ]);

    assert.deepEqual(estado.marcadores, [[30, 40]]);
    assert.equal(estado.removidos, 2);
});

test('ajusta limites quando existem varios pontos', () => {
    const { leaflet, mapa, estado } = criarLeafletFalso();
    const renderizador = criarRenderizadorMapa(leaflet, mapa);

    renderizador.desenhar([
        { latitude: 10, longitude: 20 },
        { latitude: 30, longitude: 40 }
    ]);

    assert.deepEqual(estado.limites, [
        [10, 20],
        [30, 40]
    ]);
});

test('aceita lista vazia sem criar marcadores', () => {
    const { leaflet, mapa, estado } = criarLeafletFalso();
    const renderizador = criarRenderizadorMapa(leaflet, mapa);

    renderizador.desenhar([]);

    assert.deepEqual(estado.marcadores, []);
    assert.equal(estado.limites, null);
});

test('associa um popup ao marcador quando solicitado', () => {
    const popups = [];
    const grupos = [];

    const leaflet = {
        layerGroup() {
            return {
                addTo() { return this; },
                clearLayers() {}
            };
        },
        circleMarker() {
            return {
                bindPopup(conteudo) {
                    popups.push(conteudo);
                    return this;
                },
                addTo() { return this; }
            };
        },
        latLngBounds(pontos) { return pontos; }
    };

    const mapa = {
        fitBounds() {},
        setView() {}
    };

    const renderizador = criarRenderizadorMapa(leaflet, mapa);

    renderizador.desenhar([
        {
            salto: 7,
            ip: '1.1.1.1',
            latitude: -22.9,
            longitude: -43.2
        }
    ], pontos => {
        grupos.push(pontos);
        return 'Detalhes do salto';
    });

    assert.equal(popups.length, 1);
    assert.equal(popups[0], 'Detalhes do salto');
    assert.equal(grupos[0][0].salto, 7);
});

test('agrupa detalhes de saltos com as mesmas coordenadas', () => {
    const popups = [];

    const leaflet = {
        layerGroup() {
            return {
                addTo() { return this; },
                clearLayers() {}
            };
        },
        circleMarker() {
            return {
                bindPopup(conteudo) {
                    popups.push(conteudo);
                    return this;
                },
                addTo() { return this; }
            };
        },
        latLngBounds(pontos) { return pontos; }
    };

    const mapa = {
        fitBounds() {},
        setView() {}
    };

    const renderizador = criarRenderizadorMapa(leaflet, mapa);

    renderizador.desenhar([
        { salto: 7, latitude: -22.9, longitude: -43.2 },
        { salto: 8, latitude: -22.9, longitude: -43.2 },
        { salto: 9, latitude: -22.9, longitude: -43.2 }
    ], pontos => pontos.map(p => p.salto).join(', '));

    assert.equal(popups.length, 3);

    for (const popup of popups) {
        assert.equal(popup, '7, 8, 9');
    }
});
