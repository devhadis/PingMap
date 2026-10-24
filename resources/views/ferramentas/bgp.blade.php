@extends('layouts.pingmap')

@section('title', 'BGP Lookup | PingMap')
@section('body_class', 'tools-page')

@section('content')
<style>
    body.tools-page {
        display: block;
        padding: 0;
        background: #0b1220;
    }

    body.tools-page > .card {
        width: 100%;
        max-width: none;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
    }

    .bgp-heading {
        color: #38bdf8;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .bgp-title {
        color: #f8fafc;
        font-size: 34px;
        margin: 12px 0;
    }

    .bgp-description {
        color: #94a3b8;
        line-height: 1.6;
    }

    .bgp-panel {
        background: #111c2e;
        border: 1px solid #293548;
        border-radius: 14px;
        padding: 24px;
        margin-top: 28px;
    }

    .bgp-panel h2 {
        color: #f8fafc;
        font-size: 20px;
        margin: 0 0 20px;
    }

    .bgp-form-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 160px;
        gap: 16px;
        align-items: end;
    }

    .bgp-field label {
        display: block;
        color: #cbd5e1;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .bgp-field input {
        box-sizing: border-box;
        width: 100%;
        min-height: 46px;
        padding: 11px 12px;
        border: 1px solid #475569;
        border-radius: 8px;
        background: #0b1220;
        color: #f8fafc;
    }

    .bgp-button {
        min-height: 46px;
        padding: 10px;
        background: #0284c7;
        color: white;
        border: 0;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
    }

    .bgp-button:disabled {
        opacity: .6;
        cursor: wait;
    }

    .bgp-message {
        color: #93c5fd;
        margin-top: 18px;
        overflow-wrap: anywhere;
    }

    .bgp-message.error {
        color: #fca5a5;
    }

    .bgp-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .bgp-card {
        background: #0b1220;
        border: 1px solid #293548;
        border-radius: 10px;
        padding: 18px;
        overflow-wrap: anywhere;
    }

    .bgp-card strong {
        display: block;
        color: #94a3b8;
        font-size: 13px;
        margin-bottom: 10px;
    }

    .bgp-card span {
        color: #f8fafc;
        font-size: 18px;
    }

    .bgp-prefix-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-top: 20px;
    }

    .bgp-prefix-grid h3 {
        color: #f8fafc;
        margin: 0 0 12px;
    }

    .bgp-prefix-grid ul {
        color: #cbd5e1;
        padding-left: 20px;
        overflow-wrap: anywhere;
    }

    .bgp-prefix-grid li {
        margin-bottom: 8px;
    }

    [hidden] {
        display: none !important;
    }

    @media (max-width: 850px) {
        .bgp-form-grid,
        .bgp-summary,
        .bgp-prefix-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="tools-shell">
    @include('partials.network_sidebar')

    <main class="tools-content">
        <div class="bgp-heading">
            NETWORK TOOLS / BGP INFORMATION
        </div>

        <h1 class="bgp-title">BGP Lookup</h1>

        <p class="bgp-description">
            Consulte informações de sistemas autônomos (ASN),
            organizações e prefixos IPv4 e IPv6 anunciados.
        </p>

        <section class="bgp-panel">
            <h2>Nova consulta BGP</h2>

            <form
                id="bgp-form"
                method="POST"
                action="{{ url('/api/v1/bgp/consultas') }}"
            >
                @csrf

                <div class="bgp-form-grid">
                    <div class="bgp-field">
                        <label for="bgp-asn">
                            Número do sistema autônomo (ASN)
                        </label>

                        <input
                            id="bgp-asn"
                            name="asn"
                            type="text"
                            placeholder="AS3333"
                            maxlength="32"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <button
                        id="bgp-enviar"
                        class="bgp-button"
                        type="submit"
                    >
                        Consultar BGP
                    </button>
                </div>
            </form>

            <p
                id="bgp-mensagem"
                class="bgp-message"
                role="status"
                aria-live="polite"
            ></p>
        </section>

        <section
            id="bgp-resultados"
            class="bgp-panel"
            hidden
        >
            <h2>Resultado da consulta</h2>

            <div id="bgp-resumo" class="bgp-summary"></div>

            <div class="bgp-prefix-grid">
                <div class="bgp-card">
                    <h3>
                        Prefixos IPv4:
                        <span id="bgp-total-ipv4">0</span>
                    </h3>
                    <ul id="bgp-prefixos-ipv4"></ul>
                </div>

                <div class="bgp-card">
                    <h3>
                        Prefixos IPv6:
                        <span id="bgp-total-ipv6">0</span>
                    </h3>
                    <ul id="bgp-prefixos-ipv6"></ul>
                </div>
            </div>
        </section>
    </main>
</div>

<script data-bgp-integration>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('bgp-form');
    const botao = document.getElementById('bgp-enviar');
    const mensagem = document.getElementById('bgp-mensagem');
    const painel = document.getElementById('bgp-resultados');
    const resumo = document.getElementById('bgp-resumo');

    function adicionarCampo(titulo, informacao) {
        const card = document.createElement('div');
        const rotulo = document.createElement('strong');
        const valor = document.createElement('span');

        card.className = 'bgp-card';
        rotulo.textContent = titulo;
        valor.textContent = String(informacao);

        card.append(rotulo, valor);
        resumo.appendChild(card);
    }

    function mostrarPrefixos(versao, prefixos) {
        const lista = document.getElementById(
            'bgp-prefixos-' + versao
        );

        const total = document.getElementById(
            'bgp-total-' + versao
        );

        lista.replaceChildren();
        total.textContent = String(prefixos.length);

        if (prefixos.length === 0) {
            const item = document.createElement('li');
            item.textContent = 'Nenhum prefixo encontrado.';
            lista.appendChild(item);
            return;
        }

        const limite = 100;

        for (const prefixo of prefixos.slice(0, limite)) {
            if (typeof prefixo !== 'string') {
                continue;
            }

            const item = document.createElement('li');
            item.textContent = prefixo;
            lista.appendChild(item);
        }

        if (prefixos.length > limite) {
            const item = document.createElement('li');
            item.textContent =
                'Exibindo os primeiros ' + limite +
                ' de ' + prefixos.length + ' prefixos.';
            lista.appendChild(item);
        }
    }

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const asn = document.getElementById('bgp-asn')
            .value.trim();

        const token = form.querySelector(
            '[name="_token"]'
        ).value;

        botao.disabled = true;
        painel.hidden = true;
        resumo.replaceChildren();

        mensagem.classList.remove('error');
        mensagem.textContent = 'Consultando informações BGP...';

        try {
            const resposta = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ asn })
            });

            const conteudo = await resposta.json()
                .catch(() => ({}));

            if (!resposta.ok) {
                const erros = {
                    401: 'Faça login para consultar.',
                    403: 'Acesso não autorizado.',
                    419: 'Sessão expirada. Atualize a página.',
                    422: 'Informe um ASN válido.',
                    429: 'Limite de consultas atingido. Aguarde.',
                    503: 'Serviço BGP indisponível no momento.'
                };

                throw new Error(
                    erros[resposta.status] ||
                    'Não foi possível realizar a consulta.'
                );
            }

            const resultado = conteudo.resultado;

            if (
                !resultado ||
                typeof resultado !== 'object' ||
                Array.isArray(resultado) ||
                !Number.isInteger(resultado.asn) ||
                typeof resultado.anunciado !== 'boolean' ||
                !resultado.prefixos ||
                !Array.isArray(resultado.prefixos.ipv4) ||
                !Array.isArray(resultado.prefixos.ipv6)
            ) {
                throw new Error('Resposta BGP inesperada.');
            }

            adicionarCampo('ASN', 'AS' + resultado.asn);

            adicionarCampo(
                'Organização',
                typeof resultado.nome === 'string' &&
                resultado.nome.trim() !== ''
                    ? resultado.nome
                    : 'Não informada'
            );

            adicionarCampo(
                'Estado BGP',
                resultado.anunciado
                    ? 'Anunciado'
                    : 'Não anunciado'
            );

            adicionarCampo(
                'Total de prefixos',
                resultado.prefixos.ipv4.length +
                resultado.prefixos.ipv6.length
            );

            mostrarPrefixos(
                'ipv4',
                resultado.prefixos.ipv4
            );

            mostrarPrefixos(
                'ipv6',
                resultado.prefixos.ipv6
            );

            painel.hidden = false;
            mensagem.textContent = 'Consulta concluída.';

        } catch (erro) {
            mensagem.classList.add('error');
            mensagem.textContent =
                erro instanceof Error
                    ? erro.message
                    : 'Falha inesperada na consulta.';
        } finally {
            botao.disabled = false;
        }
    });
});
</script>

@endsection
