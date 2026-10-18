import test from 'node:test';
import assert from 'node:assert/strict';

import {
    criarClienteMapa
} from '../../public/js/mapa-api.js';

function criarAmbiente(respostas) {
    const eventos = [];
    let chamadas = 0;

    const cliente = criarClienteMapa({
        consultar: async () => {
            const resposta = respostas[chamadas];
            chamadas += 1;
            return resposta;
        },
        aguardar: async () => {},
        aoEstado: mensagem => eventos.push(mensagem),
        aoConcluir: dados => eventos.push(dados)
    });

    return {
        cliente,
        eventos,
        chamadas: () => chamadas
    };
}

test('HTTP 200 entrega os saltos geolocalizados', async () => {
    const saltos = [
        { salto: 1, ip: '1.1.1.1', geo: {
            latitude: -22.9,
            longitude: -43.2
        }}
    ];

    const ambiente = criarAmbiente([
        { codigo: 200, dados: { status: 'concluido', saltos } }
    ]);

    await ambiente.cliente.selecionar(10);

    assert.deepEqual(
        ambiente.eventos.at(-1),
        saltos
    );
});

test('HTTP 202 aguarda antes de consultar novamente', async () => {
    const ambiente = criarAmbiente([
        { codigo: 202, dados: { status: 'pendente' } },
        { codigo: 200, dados: { status: 'concluido', saltos: [] } }
    ]);

    await ambiente.cliente.selecionar(20);

    assert.equal(ambiente.chamadas(), 2);
    assert.deepEqual(ambiente.eventos.at(-1), []);
});

test('HTTP 503 informa falha sem apresentar dados', async () => {
    const ambiente = criarAmbiente([
        { codigo: 503, dados: { status: 'erro' } }
    ]);

    await ambiente.cliente.selecionar(30);

    assert.equal(ambiente.chamadas(), 1);
    assert.equal(
        ambiente.eventos.some(evento => Array.isArray(evento)),
        false
    );
});

test('limita a quantidade de consultas pendentes', async () => {
    const ambiente = criarAmbiente(
        Array.from({ length: 20 }, () => ({
            codigo: 202,
            dados: { status: 'pendente' }
        }))
    );

    await ambiente.cliente.selecionar(40);

    assert.equal(ambiente.chamadas(), 8);
});

test('ignora resposta de consulta anterior', async () => {
    let liberarRespostaAntiga;

    const eventos = [];

    const cliente = criarClienteMapa({
        consultar: id => {
            if (id === 1) {
                return new Promise(resolve => {
                    liberarRespostaAntiga = resolve;
                });
            }

            return Promise.resolve({
                codigo: 200,
                dados: { status: 'concluido', saltos: [{ salto: 2 }] }
            });
        },
        aguardar: async () => {},
        aoEstado: () => {},
        aoConcluir: saltos => eventos.push(saltos)
    });

    const antiga = cliente.selecionar(1);

    await cliente.selecionar(2);

    liberarRespostaAntiga({
        codigo: 200,
        dados: { status: 'concluido', saltos: [{ salto: 1 }] }
    });

    await antiga;

    assert.deepEqual(eventos, [[{ salto: 2 }]]);
});

test('ignora resposta antiga da mesma consulta', async () => {
    let liberarPrimeira;
    let chamadas = 0;
    const eventos = [];

    const cliente = criarClienteMapa({
        consultar: () => {
            chamadas += 1;

            if (chamadas === 1) {
                return new Promise(resolve => {
                    liberarPrimeira = resolve;
                });
            }

            return Promise.resolve({
                codigo: 200,
                dados: { status: 'concluido', saltos: [{ salto: 9 }] }
            });
        },
        aguardar: async () => {},
        aoEstado: () => {},
        aoConcluir: saltos => eventos.push(saltos)
    });

    const primeira = cliente.selecionar(9);

    await cliente.selecionar(9);

    liberarPrimeira({
        codigo: 200,
        dados: { status: 'concluido', saltos: [{ salto: 1 }] }
    });

    await primeira;

    assert.deepEqual(eventos, [[{ salto: 9 }]]);
});
