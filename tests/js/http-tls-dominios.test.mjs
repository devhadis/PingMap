import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const caminho = 'resources/views/ferramentas/http_tls.blade.php';
const html = fs.readFileSync(caminho, 'utf8');

const codigo = [...html.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/gi)]
    .map((trecho) => trecho[1])
    .find((trecho) => trecho.includes('function atualizarDominios()'));

assert.ok(codigo, 'Script HTTP/TLS não encontrado na view');

function criarElemento(tag = 'div') {
    return {
        tag,
        children: [],
        eventos: {},
        atributos: {},
        textContent: '',
        value: '',
        hidden: false,
        disabled: false,
        style: {},
        dataset: {},
        classList: { add() {}, remove() {}, toggle() {} },

        appendChild(filho) {
            this.children.push(filho);
            return filho;
        },

        append(...filhos) {
            this.children.push(...filhos);
        },

        replaceChildren(...filhos) {
            this.children = [...filhos];
        },

        addEventListener(evento, funcao) {
            this.eventos[evento] = funcao;
        },

        setAttribute(nome, valor) {
            this.atributos[nome] = String(valor);
        },

        getAttribute(nome) {
            return this.atributos[nome] ?? null;
        }
    };
}

function preparar(dominios) {
    const elementos = {};

    const form = criarElemento('form');
    form.action = '/api/v1/http-tls/consultas';
    form.querySelector = () => ({ value: 'token-teste' });

    elementos['http-tls-form'] = form;
    elementos['http-tls-dominio'] = criarElemento('input');
    elementos['http-tls-dominio'].value = 'example.com';

    const document = {
        addEventListener(evento, funcao) {
            if (evento === 'DOMContentLoaded') funcao();
        },

        getElementById(id) {
            return elementos[id] ??= criarElemento();
        },

        createElement: criarElemento
    };

    const resultado = {
        dominio: 'example.com',
        status_http: 200,
        tempo_ms: 20,
        ip_conectado: '1.1.1.1',
        ips: ['1.1.1.1'],
        cabecalhos: {},
        certificado: {
            expirado: false,
            alerta_expiracao: false,
            dominios
        }
    };

    const fetch = async () => ({
        ok: true,
        status: 200,
        json: async () => ({ resultado })
    });

    vm.runInNewContext(
        codigo,
        { document, fetch, console },
        { filename: caminho }
    );

    return {
        elementos,

        async consultar() {
            assert.equal(typeof form.eventos.submit, 'function');

            await form.eventos.submit({
                preventDefault() {}
            });
        },

        clicar() {
            const toggle = elementos['http-tls-dominios-toggle'];

            assert.equal(typeof toggle.eventos.click, 'function');

            toggle.eventos.click();
        },

        lista() {
            return elementos['http-tls-dominios-lista'].children
                .map((item) => item.textContent);
        },

        toggle() {
            return elementos['http-tls-dominios-toggle'];
        }
    };
}

const seteDominios = Array.from(
    { length: 7 },
    (_, i) => `sub${i + 1}.example.com`
);

test('mostra inicialmente apenas cinco domínios', async () => {
    const pagina = preparar(seteDominios);

    await pagina.consultar();

    assert.deepEqual(pagina.lista(), seteDominios.slice(0, 5));
    assert.equal(pagina.toggle().hidden, false);
    assert.equal(
        pagina.toggle().textContent,
        'Mostrar todos os domínios'
    );
    assert.equal(
        pagina.toggle().getAttribute('aria-expanded'),
        'false'
    );
});

test('expande para mostrar todos os domínios', async () => {
    const pagina = preparar(seteDominios);

    await pagina.consultar();
    pagina.clicar();

    assert.deepEqual(pagina.lista(), seteDominios);
    assert.equal(
        pagina.toggle().textContent,
        'Mostrar menos domínios'
    );
    assert.equal(
        pagina.toggle().getAttribute('aria-expanded'),
        'true'
    );
});

test('recolhe novamente para cinco domínios', async () => {
    const pagina = preparar(seteDominios);

    await pagina.consultar();
    pagina.clicar();
    pagina.clicar();

    assert.deepEqual(pagina.lista(), seteDominios.slice(0, 5));
    assert.equal(
        pagina.toggle().textContent,
        'Mostrar todos os domínios'
    );
    assert.equal(
        pagina.toggle().getAttribute('aria-expanded'),
        'false'
    );
});

test('oculta o botão quando há cinco domínios ou menos', async () => {
    for (const quantidade of [0, 1, 5]) {
        const pagina = preparar(
            seteDominios.slice(0, quantidade)
        );

        await pagina.consultar();

        assert.equal(pagina.toggle().hidden, true);
        assert.deepEqual(
            pagina.lista(),
            seteDominios.slice(0, quantidade)
        );
    }
});

test('preserva nomes externos como texto', async () => {
    const texto = '<img src=x onerror=alert(1)>';
    const pagina = preparar([texto]);

    await pagina.consultar();

    assert.deepEqual(pagina.lista(), [texto]);
    assert.equal(
        pagina.elementos['http-tls-dominios-lista'].children[0].tag,
        'li'
    );
});
