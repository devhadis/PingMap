@extends('layouts.pingmap')

@section('title', 'TCP Port Check | PingMap')
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

    .tcp-eyebrow {
        color: #38bdf8;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .tcp-title {
        color: #f8fafc;
        font-size: 34px;
        margin: 12px 0;
    }

    .tcp-description {
        color: #94a3b8;
        margin-bottom: 30px;
    }

    .tcp-panel {
        background: #111c2e;
        border: 1px solid #293548;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 22px;
    }

    .tcp-panel h2 {
        margin: 0 0 20px;
        color: #f8fafc;
        font-size: 20px;
    }

    .tcp-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 160px 180px;
        gap: 14px;
        align-items: end;
    }

    .tcp-field label {
        display: block;
        color: #cbd5e1;
        margin-bottom: 8px;
        font-size: 13px;
    }

    .tcp-field input,
    .tcp-field select {
        width: 100%;
        box-sizing: border-box;
        min-height: 46px;
        padding: 11px 12px;
        color: #f8fafc;
        background: #0b1220;
        border: 1px solid #40516a;
        border-radius: 8px;
    }

    .tcp-button {
        min-height: 46px;
        border: 0;
        border-radius: 8px;
        color: white;
        background: #0284c7;
        font-weight: 700;
        cursor: pointer;
    }

    .tcp-button:disabled {
        opacity: .6;
        cursor: wait;
    }

    .tcp-muted {
        color: #94a3b8;
    }

    .tcp-message {
        margin-top: 16px;
        color: #7dd3fc;
    }

    .tcp-error {
        color: #fca5a5;
    }

    .tcp-table-wrap {
        overflow-x: auto;
    }

    .tcp-table {
        width: 100%;
        border-collapse: collapse;
        color: #e2e8f0;
        text-align: left;
    }

    .tcp-table th,
    .tcp-table td {
        padding: 14px 10px;
        border-bottom: 1px solid #293548;
    }

    .tcp-table th {
        color: #94a3b8;
        font-size: 13px;
    }

    @media (max-width: 900px) {
        .tcp-form {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="tools-shell">
    @include('partials.network_sidebar')

    <main class="tools-content">
        <div class="tcp-eyebrow">NETWORK TOOLS / TCP</div>

        <h1 class="tcp-title">TCP Port Check</h1>

        <p class="tcp-description">
            Verifique a conectividade TCP de um IP público
            e acompanhe o tempo de conexão.
        </p>

        <section class="tcp-panel">
            <h2>Nova consulta TCP</h2>

            <form id="tcp-form" class="tcp-form">
                @csrf

                <div class="tcp-field">
                    <label for="tcp-destino">Endereço IPv4 público</label>
                    <input
                        id="tcp-destino"
                        name="destino"
                        type="text"
                        value="1.1.1.1"
                        maxlength="45"
                        required
                    >
                </div>

                <div class="tcp-field">
                    <label for="tcp-porta">Porta TCP</label>
                    <select id="tcp-porta" name="porta">
                        <option value="443">443 — HTTPS</option>
                        <option value="80">80 — HTTP</option>
                    </select>
                </div>

                <button
                    id="tcp-enviar"
                    class="tcp-button"
                    type="submit"
                >
                    Verificar TCP
                </button>
            </form>

            <p id="tcp-mensagem" class="tcp-message" role="status">
                Pronto para executar uma consulta.
            </p>
        </section>

        <section class="tcp-panel">
            <h2>Resultado da consulta</h2>
            <p id="tcp-resultado" class="tcp-muted" aria-live="polite">
                Nenhuma consulta executada nesta página.
            </p>
        </section>

        <section class="tcp-panel">
            <h2>Histórico TCP</h2>

            <div class="tcp-table-wrap">
                <table class="tcp-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Destino</th>
                            <th>Porta</th>
                            <th>Status</th>
                            <th>Tempo TCP</th>
                        </tr>
                    </thead>
                    <tbody id="tcp-historico">
                        <tr>
                            <td colspan="5">Carregando histórico...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
(() => {
    const formulario = document.getElementById('tcp-form');
    const botao = document.getElementById('tcp-enviar');
    const mensagem = document.getElementById('tcp-mensagem');
    const resultado = document.getElementById('tcp-resultado');
    const historico = document.getElementById('tcp-historico');

    const base = '/api/v1/tcp/consultas';
    const token = formulario.querySelector('input[name="_token"]').value;

    function mostrarMensagem(texto, erro = false) {
        mensagem.textContent = texto;
        mensagem.classList.toggle('tcp-error', erro);
    }

    async function requisitar(url, opcoes = {}) {
        const resposta = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                ...(opcoes.headers || {})
            },
            ...opcoes
        });

        const dados = await resposta.json();

        if (!resposta.ok) {
            throw new Error(
                dados.message || `Erro HTTP ${resposta.status}`
            );
        }

        return dados;
    }

    function tempo(valor) {
        return valor === null || valor === undefined
            ? '—'
            : `${valor} ms`;
    }

    function preencherHistorico(consultas) {
        historico.replaceChildren();

        if (!Array.isArray(consultas) || consultas.length === 0) {
            const linha = historico.insertRow();
            const celula = linha.insertCell();
            celula.colSpan = 5;
            celula.textContent = 'Nenhuma consulta registrada.';
            return;
        }

        consultas.forEach(consulta => {
            const linha = historico.insertRow();

            [
                consulta.id,
                consulta.destino,
                consulta.porta,
                consulta.status,
                tempo(consulta.tempo_ms)
            ].forEach(valor => {
                linha.insertCell().textContent = String(valor ?? '—');
            });
        });
    }

    async function atualizarHistorico() {
        const consultas = await requisitar(base);
        preencherHistorico(consultas);
    }

    async function acompanhar(id) {
        for (let tentativa = 0; tentativa < 15; tentativa++) {
            await new Promise(resolve => setTimeout(resolve, 1000));

            const consulta = await requisitar(`${base}/${id}`);

            resultado.textContent =
                `Consulta #${consulta.id} — ` +
                `${consulta.destino}:${consulta.porta} — ` +
                `${consulta.status} — ${tempo(consulta.tempo_ms)}`;

            if (!['pendente', 'executando'].includes(consulta.status)) {
                mostrarMensagem(
                    consulta.status === 'conectado'
                        ? 'Consulta TCP concluída.'
                        : `Consulta finalizada: ${consulta.status}.`,
                    consulta.status === 'erro'
                );

                await atualizarHistorico();
                return;
            }
        }

        mostrarMensagem(
            'Consulta ainda em processamento. Atualize o histórico mais tarde.'
        );
    }

    formulario.addEventListener('submit', async evento => {
        evento.preventDefault();
        botao.disabled = true;

        mostrarMensagem('Enviando consulta TCP...');
        resultado.textContent = 'Aguardando processamento...';

        try {
            const destino = document.getElementById('tcp-destino').value.trim();
            const porta = Number(document.getElementById('tcp-porta').value);

            const consulta = await requisitar(base, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ destino, porta })
            });

            mostrarMensagem(`Consulta #${consulta.id} enviada.`);
            await acompanhar(consulta.id);
        } catch (erro) {
            mostrarMensagem(erro.message, true);
            resultado.textContent = 'Não foi possível concluir a consulta.';
        } finally {
            botao.disabled = false;
        }
    });

    atualizarHistorico().catch(() => {
        preencherHistorico([]);
        mostrarMensagem('Não foi possível carregar o histórico.', true);
    });
})();
</script>
@endsection
