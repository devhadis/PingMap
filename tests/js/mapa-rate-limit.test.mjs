import test from 'node:test';
import assert from 'node:assert/strict';

import { criarClienteMapa } from '../../public/js/mapa-api.js';

test('mapa informa limite de consultas ao receber HTTP 429', async () => {
    const mensagens = [];
    let chamadas = 0;

    const cliente = criarClienteMapa({
        consultar: async () => {
            chamadas++;

            return {
                codigo: 429,
                dados: {
                    message: 'Too Many Requests'
                }
            };
        },
        aguardar: async () => {},
        aoEstado: mensagem => mensagens.push(mensagem)
    });

    await cliente.selecionar(9);

    assert.equal(chamadas, 1);
    assert.equal(
        mensagens.at(-1),
        'Limite de consultas atingido. Aguarde antes de tentar novamente.'
    );
});
