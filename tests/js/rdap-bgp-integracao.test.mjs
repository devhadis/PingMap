import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const arquivo = readFileSync(
    new URL('../../resources/views/ferramentas/rdap.blade.php', import.meta.url),
    'utf8'
);

const script = arquivo.match(
    /<script data-rdap-integration>([\s\S]*?)<\/script>/
)?.[1];

assert.ok(script, 'JavaScript RDAP não encontrado');

function criarElemento() {
    return {
        hidden: false,
        disabled: false,
        value: '',
        textContent: '',
        children: [],
        eventos: {},

        classList: {
            add() {},
            remove() {}
        },

        addEventListener(nome, funcao) {
            this.eventos[nome] = funcao;
        },

        querySelector() {
            return { value: 'token-de-teste' };
        },

        replaceChildren(...filhos) {
            this.children = filhos;
        },

        append(...filhos) {
            this.children.push(...filhos);
        },

        appendChild(filho) {
            this.children.push(filho);
        }
    };
}

function prepararPagina(responderBgp = null) {
    const ids = [
        'rdap-form',
        'rdap-ip',
        'rdap-enviar',
        'rdap-mensagem',
        'rdap-resultado',
        'rdap-dados',
        'rdap-bgp-consultar',
        'rdap-bgp-resultado',
        'rdap-bgp-mensagem',
        'rdap-bgp-dados'
    ];

    const elementos = Object.fromEntries(
        ids.map(id => [id, criarElemento()])
    );

    elementos['rdap-ip'].value = '8.8.8.8';
    elementos['rdap-form'].action = '/api/v1/rdap/consultas';

    const chamadas = [];

    const document = {
        getElementById(id) {
            assert.ok(elementos[id], `Elemento ausente: ${id}`);
            return elementos[id];
        },

        createElement() {
            return criarElemento();
        },

        addEventListener(nome, funcao) {
            if (nome === 'DOMContentLoaded') {
                funcao();
            }
        }
    };

    async function fetch(url, opcoes) {
        chamadas.push({ url, opcoes });

        if (url === '/api/v1/rdap/consultas') {
            return {
                ok: true,
                status: 200,
                json: async () => ({
                    resultado: {
                        nome: 'GOGL',
                        inicio: '8.8.8.0',
                        fim: '8.8.8.255',
                        versao: 'IPv4'
                    }
                })
            };
        }

        if (url === '/api/v1/bgp/rede/consultas') {
            if (responderBgp !== null) {
                return responderBgp(url, opcoes);
            }

            return {
                ok: true,
                status: 200,
                json: async () => ({
                    resultado: {
                        tipo: 'ip',
                        consulta: '8.8.8.8',
                        prefixo: '8.8.8.0/24',
                        asns: [15169],
                        anunciado: true
                    }
                })
            };
        }

        throw new Error(`URL inesperada: ${url}`);
    }

    runInNewContext(script, { document, fetch });

    return { elementos, chamadas };
}

test('consulta BGP após RDAP e exibe prefixo e ASN', async () => {
    const { elementos, chamadas } = prepararPagina();

    await elementos['rdap-form'].eventos.submit({
        preventDefault() {}
    });

    assert.equal(
        elementos['rdap-bgp-consultar'].hidden,
        false
    );

    await elementos['rdap-bgp-consultar'].eventos.click();

    assert.equal(chamadas.length, 2);

    assert.equal(
        chamadas[1].url,
        '/api/v1/bgp/rede/consultas'
    );

    assert.deepEqual(
        JSON.parse(chamadas[1].opcoes.body),
        { alvo: '8.8.8.8' }
    );

    assert.equal(
        elementos['rdap-bgp-mensagem'].textContent,
        'Consulta BGP concluída.'
    );

    const texto = elementos['rdap-bgp-dados'].children
        .flatMap(linha => linha.children)
        .map(elemento => elemento.textContent)
        .join(' ');

    assert.match(texto, /8\.8\.8\.0\/24/);
    assert.match(texto, /15169/);
    assert.match(texto, /Sim/);
});


test('falha BGP preserva resultado RDAP', async () => {
    const { elementos } = prepararPagina(async () => ({
        ok: false,
        status: 503,
        json: async () => ({})
    }));

    await elementos['rdap-form'].eventos.submit({
        preventDefault() {}
    });

    await elementos['rdap-bgp-consultar'].eventos.click();

    assert.equal(elementos['rdap-resultado'].hidden, false);
    assert.ok(elementos['rdap-dados'].children.length > 0);

    assert.equal(
        elementos['rdap-bgp-mensagem'].textContent,
        'Serviço BGP indisponível.'
    );

    assert.equal(
        elementos['rdap-bgp-consultar'].disabled,
        false
    );
});

test('resposta BGP antiga não aparece em nova consulta', async () => {
    let concluirBgp;

    const pendente = new Promise(resolve => {
        concluirBgp = resolve;
    });

    const { elementos } = prepararPagina(() => pendente);

    await elementos['rdap-form'].eventos.submit({
        preventDefault() {}
    });

    const consultaAntiga =
        elementos['rdap-bgp-consultar'].eventos.click();

    elementos['rdap-ip'].value = '1.1.1.1';

    await elementos['rdap-form'].eventos.submit({
        preventDefault() {}
    });

    concluirBgp({
        ok: true,
        status: 200,
        json: async () => ({
            resultado: {
                tipo: 'ip',
                consulta: '8.8.8.8',
                prefixo: '8.8.8.0/24',
                asns: [15169],
                anunciado: true
            }
        })
    });

    await consultaAntiga;

    assert.equal(elementos['rdap-resultado'].hidden, false);
    assert.equal(elementos['rdap-bgp-resultado'].hidden, true);
    assert.equal(elementos['rdap-bgp-dados'].children.length, 0);
    assert.equal(elementos['rdap-bgp-consultar'].disabled, false);
});
