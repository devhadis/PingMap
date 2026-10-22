import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const caminho = 'public/js/dnssec-analise.js';

function preparar(resposta) {
    const elementos = {};
    const ids = [
        'dnssec-form',
        'dnssec-dominio',
        'dnssec-enviar',
        'dnssec-mensagem',
        'dnssec-resultado',
        'dnssec-res-dominio',
        'dnssec-res-tipo',
        'dnssec-res-estado',
        'dnssec-explicacao'
    ];

    for (const id of ids) {
        elementos[id] = {
            value: '',
            textContent: '',
            hidden: false,
            disabled: false,
            dataset: {},
            classList: { toggle() {} },
            addEventListener(evento, funcao) {
                this[evento] = funcao;
            }
        };
    }

    const form = elementos['dnssec-form'];
    form.dataset.apiUrl = '/api/v1/dnssec/analises';
    form.reportValidity = () => true;

    elementos['dnssec-dominio'].value = 'cloudflare.com';

    let requisicao;

    const contexto = {
        document: {
            getElementById: id => elementos[id] ?? null,
            querySelector: () => ({ content: 'token-teste' })
        },
        fetch: async (url, opcoes) => {
            requisicao = { url, opcoes };
            return resposta;
        },
        AbortController,
        setTimeout: () => 1,
        clearTimeout: () => {}
    };

    const codigo = fs.readFileSync(caminho, 'utf8');
    vm.runInNewContext(codigo, contexto);

    return {
        elementos,
        requisicao: () => requisicao,
        enviar: async () => {
            await form.submit({
                preventDefault() {}
            });
        }
    };
}

test('envia domínio com CSRF e apresenta estado seguro', async () => {
    const pagina = preparar({
        ok: true,
        status: 200,
        json: async () => ({
            resultado: {
                dominio: 'cloudflare.com',
                tipo: 'A',
                estado: 'seguro'
            }
        })
    });

    await pagina.enviar();

    const requisicao = pagina.requisicao();

    assert.equal(requisicao.url, '/api/v1/dnssec/analises');
    assert.equal(requisicao.opcoes.method, 'POST');
    assert.equal(requisicao.opcoes.credentials, 'same-origin');
    assert.equal(
        requisicao.opcoes.headers['X-CSRF-TOKEN'],
        'token-teste'
    );
    assert.equal(
        JSON.parse(requisicao.opcoes.body).dominio,
        'cloudflare.com'
    );
    assert.equal(
        pagina.elementos['dnssec-res-estado'].textContent,
        'Seguro'
    );
    assert.equal(
        pagina.elementos['dnssec-resultado'].hidden,
        false
    );
});

test('informa sessão expirada ao receber HTTP 419', async () => {
    const pagina = preparar({
        ok: false,
        status: 419
    });

    await pagina.enviar();

    assert.match(
        pagina.elementos['dnssec-mensagem'].textContent,
        /sessão/i
    );
    assert.equal(
        pagina.elementos['dnssec-resultado'].hidden,
        true
    );
});

test('informa limite de consultas ao receber HTTP 429', async () => {
    const pagina = preparar({
        ok: false,
        status: 429
    });

    await pagina.enviar();

    assert.match(
        pagina.elementos['dnssec-mensagem'].textContent,
        /limite/i
    );
});

test('não apresenta resultado quando API retorna estado desconhecido', async () => {
    const pagina = preparar({
        ok: true,
        status: 200,
        json: async () => ({
            resultado: {
                dominio: 'cloudflare.com',
                tipo: 'A',
                estado: 'desconhecido'
            }
        })
    });

    await pagina.enviar();

    assert.equal(
        pagina.elementos['dnssec-resultado'].hidden,
        true
    );
    assert.match(
        pagina.elementos['dnssec-mensagem'].textContent,
        /inválid|inesperad|erro/i
    );
});
