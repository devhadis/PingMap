@extends('layouts.pingmap')

@section('title', 'DNS Lookup | PingMap')
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

    .tools-shell {
        display: grid;
        grid-template-columns: 250px minmax(0, 1fr);
        min-height: 100vh;
    }

    .tools-sidebar {
        background: #111827;
        border-right: 1px solid #293548;
        padding: 28px 16px;
    }

    .tools-brand {
        display: block;
        color: #38bdf8;
        font-size: 25px;
        font-weight: 800;
        text-decoration: none;
        margin: 0 10px 35px;
    }

    .tools-group {
        color: #94a3b8;
        font-size: 11px;
        font-weight: bold;
        letter-spacing: 1.5px;
        margin: 26px 12px 10px;
    }

    .tools-link {
        display: block;
        color: #cbd5e1;
        text-decoration: none;
        padding: 12px 14px;
        border-radius: 9px;
        margin: 4px 0;
    }

    .tools-link:hover {
        background: #1e293b;
    }

    .tools-link.active {
        color: #7dd3fc;
        background: #17334e;
        font-weight: bold;
    }

    .tools-link.disabled {
        color: #64748b;
        cursor: not-allowed;
    }

    .tools-content {
        width: 100%;
        max-width: 1250px;
        padding: 42px;
        min-width: 0;
    }

    .tools-eyebrow {
        color: #38bdf8;
        font-size: 12px;
        font-weight: bold;
        letter-spacing: 1px;
    }

    .tools-content h1 {
        color: #f8fafc;
        margin: 12px 0;
        font-size: 34px;
    }

    .tools-description {
        color: #94a3b8;
        margin-bottom: 30px;
    }

    .tools-panel {
        background: #111c2e;
        border: 1px solid #293548;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 22px;
        min-width: 0;
    }

    .tools-panel h2 {
        font-size: 19px;
        margin: 0 0 18px;
    }

    .tools-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 150px 180px;
        align-items: end;
        gap: 14px;
    }

    .tools-form label {
        margin: 0 0 8px;
        color: #cbd5e1;
        font-size: 13px;
    }

    .tools-form input,
    .tools-form select {
        width: 100%;
        height: 46px;
        border: 1px solid #475569;
        border-radius: 8px;
        background: #0b1220;
        color: white;
        padding: 10px 12px;
    }

    .tools-form button {
        height: 46px;
        margin: 0;
    }

    .tools-form button:disabled {
        opacity: .6;
        cursor: wait;
    }

    .tools-message {
        color: #93c5fd;
        margin: 16px 0 0;
        overflow-wrap: anywhere;
    }

    .tools-message.error {
        color: #fca5a5;
    }

    .tools-table-wrap {
        overflow-x: auto;
    }

    .tools-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 14px;
    }

    .tools-table th,
    .tools-table td {
        padding: 13px;
        border-bottom: 1px solid #293548;
        vertical-align: top;
    }

    .tools-table th {
        color: #94a3b8;
        font-weight: 600;
    }

    .tools-table td {
        overflow-wrap: anywhere;
    }

    .tools-value {
        font-family: monospace;
        color: #bae6fd;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .tools-muted {
        color: #94a3b8;
    }

    .tools-status {
        color: #86efac;
    }

    @media (max-width: 850px) {
        .tools-shell {
            grid-template-columns: 1fr;
        }

        .tools-sidebar {
            border-right: 0;
            border-bottom: 1px solid #293548;
            padding: 16px;
        }

        .tools-brand {
            margin-bottom: 12px;
        }

        .tools-content {
            padding: 22px 14px;
        }

        .tools-form {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="tools-shell">
    @include('partials.network_sidebar')

    <main class="tools-content">
        <div class="tools-eyebrow">NETWORK TOOLS / DNS</div>
        <h1>DNS Lookup</h1>
        <p class="tools-description">
            Consulte registros DNS públicos e acompanhe os resultados.
        </p>

        <section class="tools-panel">
            <h2>Nova consulta DNS</h2>

            <form id="form-dns" class="tools-form">
                @csrf

                <div>
                    <label for="dns-dominio">Domínio ou IP público para PTR</label>
                    <input
                        id="dns-dominio"
                        name="dominio"
                        value="google.com"
                        maxlength="253"
                        autocomplete="off"
                        required
                    >
                </div>

                <div>
                    <label for="dns-tipo">Tipo de registro</label>
                    <select id="dns-tipo" name="tipo">
                        @foreach(['A', 'AAAA', 'MX', 'TXT', 'NS', 'CNAME', 'SOA', 'PTR', 'CAA'] as $tipo)
                            <option value="{{ $tipo }}">{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>

                <button id="dns-executar" type="submit">
                    Consultar DNS
                </button>
            </form>

            <p id="dns-mensagem"
               class="tools-message"
               role="status"
               aria-live="polite"></p>
        </section>

        <section class="tools-panel">
            <h2>Resultado da consulta</h2>

            <p id="dns-resumo" class="tools-muted">
                Execute uma consulta para visualizar os registros.
            </p>

            <div class="tools-table-wrap">
                <table class="tools-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Valor / Destino</th>
                            <th>TTL</th>
                            <th>Prioridade</th>
                        </tr>
                    </thead>
                    <tbody id="dns-resultados"></tbody>
                </table>
            </div>
        </section>

        <section class="tools-panel">
            <h2>Minhas últimas consultas</h2>

            <div class="tools-table-wrap">
                <table class="tools-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Domínio</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Registros</th>
                        </tr>
                    </thead>
                    <tbody id="dns-historico">
                        <tr>
                            <td colspan="5" class="tools-muted">
                                Carregando histórico...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
(() => {
    'use strict';

    const api = @json(url('/api/v1/dns/consultas'));
    const form = document.getElementById('form-dns');
    const botao = document.getElementById('dns-executar');
    const mensagem = document.getElementById('dns-mensagem');
    const resumo = document.getElementById('dns-resumo');
    const resultados = document.getElementById('dns-resultados');
    const historico = document.getElementById('dns-historico');
    const csrf = form.querySelector('[name="_token"]').value;
    const esperar = ms => new Promise(resolve => setTimeout(resolve, ms));

    function avisar(texto, erro = false) {
        mensagem.textContent = texto;
        mensagem.classList.toggle('error', erro);
    }

    async function requisitar(url, opcoes = {}) {
        const resposta = await fetch(url, {
            ...opcoes,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                ...(opcoes.headers || {})
            }
        });

        const dados = await resposta.json();

        if (!resposta.ok) {
            throw new Error(
                dados.message || `Erro HTTP ${resposta.status}`
            );
        }

        return dados;
    }

    function adicionarCelula(linha, valor, classe = '') {
        const celula = document.createElement('td');
        celula.textContent = String(valor ?? '—');

        if (classe) celula.className = classe;

        linha.appendChild(celula);
    }

    function valorRegistro(registro) {
        if (registro.ip) return registro.ip;
        if (registro.ipv6) return registro.ipv6;
        if (registro.target) return registro.target;
        if (registro.txt) return registro.txt;
        if (registro.mname) return registro.mname;
        if (registro.value) return registro.value;

        return JSON.stringify(registro);
    }

    function exibirConsulta(consulta) {
        resultados.replaceChildren();

        resumo.textContent =
            `${consulta.dominio} · ${consulta.tipo} · ` +
            `${consulta.status} · ${consulta.quantidade ?? 0} registro(s)`;

        for (const registro of consulta.registros || []) {
            const linha = document.createElement('tr');

            adicionarCelula(linha, registro.type || consulta.tipo);
            adicionarCelula(linha, valorRegistro(registro), 'tools-value');
            adicionarCelula(linha, registro.ttl);
            adicionarCelula(linha, registro.pri);

            resultados.appendChild(linha);
        }

        if (!consulta.registros?.length) {
            const linha = document.createElement('tr');
            const celula = document.createElement('td');

            celula.colSpan = 4;
            celula.textContent =
                consulta.status === 'erro'
                    ? 'A consulta não pôde ser concluída.'
                    : 'Nenhum registro encontrado.';

            linha.appendChild(celula);
            resultados.appendChild(linha);
        }
    }

    async function carregarHistorico() {
        const consultas = await requisitar(api);
        historico.replaceChildren();

        for (const consulta of consultas) {
            const linha = document.createElement('tr');

            adicionarCelula(linha, `#${consulta.id}`);
            adicionarCelula(linha, consulta.dominio);
            adicionarCelula(linha, consulta.tipo);
            adicionarCelula(linha, consulta.status);
            adicionarCelula(linha, consulta.quantidade);

            historico.appendChild(linha);
        }

        if (!consultas.length) {
            const linha = document.createElement('tr');
            const celula = document.createElement('td');

            celula.colSpan = 5;
            celula.textContent = 'Nenhuma consulta realizada.';
            linha.appendChild(celula);
            historico.appendChild(linha);
        }
    }

    form.addEventListener('submit', async evento => {
        evento.preventDefault();
        botao.disabled = true;
        resultados.replaceChildren();
        resumo.textContent = 'Aguardando o processamento DNS...';
        avisar('Enviando consulta para o Worker...');

        try {
            const criada = await requisitar(api, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    dominio: form.dominio.value.trim(),
                    tipo: form.tipo.value
                })
            });

            let concluida = false;

            for (let tentativa = 0; tentativa < 12; tentativa++) {
                await esperar(1500);

                const consulta = await requisitar(
                    `${api}/${criada.id}`
                );

                if (['sucesso', 'sem_registros', 'erro']
                    .includes(consulta.status)) {
                    exibirConsulta(consulta);
                    avisar(
                        consulta.status === 'erro'
                            ? 'O resolvedor DNS não concluiu a consulta.'
                            : 'Consulta DNS concluída.',
                        consulta.status === 'erro'
                    );
                    concluida = true;
                    break;
                }
            }

            if (!concluida) {
                avisar(
                    `Consulta #${criada.id} ainda em processamento. ` +
                    'Atualize a página para verificar o histórico.'
                );
            }

            await carregarHistorico();
        } catch (erro) {
            avisar(erro.message || 'Falha na consulta DNS.', true);
        } finally {
            botao.disabled = false;
        }
    });

    carregarHistorico().catch(() => {
        historico.replaceChildren();
        avisar('Não foi possível carregar o histórico DNS.', true);
    });
})();
</script>
@endsection
