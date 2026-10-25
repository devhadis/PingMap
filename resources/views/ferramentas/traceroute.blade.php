@extends('layouts.pingmap')

@section('title', 'Traceroute | PingMap')
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

    .trace-eyebrow {
        color: #38bdf8;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .trace-title {
        color: #f8fafc;
        font-size: 34px;
        margin: 12px 0;
    }

    .trace-description {
        color: #94a3b8;
        margin-bottom: 28px;
    }

    .trace-panel {
        background: #111c2e;
        border: 1px solid #293548;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 22px;
    }

    .trace-panel h2 {
        color: #f8fafc;
        margin: 0 0 18px;
        font-size: 20px;
    }

    .trace-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 190px;
        gap: 14px;
        align-items: end;
    }

    .trace-field label {
        display: block;
        color: #cbd5e1;
        margin-bottom: 8px;
        font-size: 13px;
    }

    .trace-field input {
        width: 100%;
        box-sizing: border-box;
        min-height: 46px;
        padding: 11px 12px;
        color: #f8fafc;
        background: #0b1220;
        border: 1px solid #40516a;
        border-radius: 8px;
    }

    .trace-button {
        min-height: 46px;
        padding: 11px 16px;
        border: 0;
        border-radius: 8px;
        color: #fff;
        background: #0284c7;
        font-weight: 700;
        cursor: pointer;
    }

    .trace-button:disabled {
        opacity: .6;
        cursor: wait;
    }

    .trace-muted {
        color: #94a3b8;
    }

    .trace-status {
        margin-top: 18px;
        color: #cbd5e1;
        min-height: 24px;
    }

    .trace-table-wrap {
        overflow-x: auto;
    }

    .trace-table {
        width: 100%;
        border-collapse: collapse;
        color: #e2e8f0;
        text-align: left;
    }

    .trace-table th,
    .trace-table td {
        padding: 13px 12px;
        border-bottom: 1px solid #293548;
    }

    .trace-table th {
        color: #94a3b8;
        font-size: 12px;
        letter-spacing: .5px;
    }

    .trace-history-button {
        color: #7dd3fc;
        background: transparent;
        border: 0;
        padding: 4px 0;
        cursor: pointer;
        text-align: left;
        font: inherit;
    }

    .trace-history-button:hover {
        text-decoration: underline;
    }

    .trace-summary {
        color: #cbd5e1;
        margin: 0 0 14px;
    }

    @media (max-width: 650px) {
        .trace-form {
            grid-template-columns: 1fr;
        }

        .trace-panel {
            padding: 16px;
        }

        .trace-title {
            font-size: 27px;
        }
    }

    /* Sprint 18: gráfico de latência por salto */
    .trace-chart {
        display: grid;
        gap: 12px;
        margin: 18px 0 26px;
    }

    .trace-chart-row {
        display: grid;
        grid-template-columns: 75px minmax(0, 1fr) 110px;
        gap: 12px;
        align-items: center;
        color: #cbd5e1;
        font-size: 13px;
    }

    .trace-chart-track {
        height: 12px;
        background: #0b1220;
        border: 1px solid #293548;
        border-radius: 8px;
        overflow: hidden;
    }

    .trace-chart-bar {
        height: 100%;
        background: #0ea5e9;
        border-radius: 8px;
    }

    .trace-chart-timeout {
        color: #fbbf24;
    }

    .trace-chart-value {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    @media (max-width: 600px) {
        .trace-chart-row {
            grid-template-columns: 48px minmax(0, 1fr) 90px;
            gap: 8px;
            font-size: 11px;
        }
    }
</style>

<div class="tools-shell">
    @include('partials.network_sidebar')
    <div class="tools-content">


<div class="trace-eyebrow">NETWORK INTELLIGENCE / DIAGNÓSTICO</div>

<h1 class="trace-title">Traceroute</h1>

<p class="trace-description">
    Descubra os saltos percorridos até um endereço IPv4 ou IPv6 público.
    Os resultados são processados de forma assíncrona.
</p>

<section class="trace-panel" aria-labelledby="trace-form-title">
    <h2 id="trace-form-title">Nova consulta</h2>

    <form id="trace-form" class="trace-form" method="post" action="{{ url()->current() }}">
        @csrf

        <div class="trace-field">
            <label for="trace-destino">Endereço IPv4 ou IPv6 público</label>
            <input
                id="trace-destino"
                name="destino"
                type="text"
                value="1.1.1.1"
                placeholder="Ex.: 1.1.1.1"
                maxlength="45"
                required
                autocomplete="off"
            >
        </div>

        <button id="trace-submit" class="trace-button" type="submit" disabled>
            Executar Traceroute
        </button>
    </form>

    <div id="trace-status" class="trace-status" role="status" aria-live="polite">
        Informe um endereço IPv4 ou IPv6 público para iniciar.
    </div>
</section>

<section class="trace-panel" aria-labelledby="trace-results-title">
    <h2 id="trace-results-title">Resultado da rota</h2>

    <p id="trace-summary" class="trace-summary">
        Nenhuma consulta selecionada.
    </p>

    <div class="trace-indicators" aria-label="Indicadores da rota"
         style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin:20px 0;">
        <div class="trace-panel">
            <strong>Saltos com resposta</strong>
            <p id="trace-respondidos">—</p>
        </div>
        <div class="trace-panel">
            <strong>Saltos sem resposta</strong>
            <p id="trace-sem-resposta">—</p>
        </div>
        <div class="trace-panel">
            <strong>Destino alcançado</strong>
            <p id="trace-destino-alcancado">—</p>
        </div>
        <div class="trace-panel">
            <strong>Latência do destino</strong>
            <p id="trace-latencia-destino">—</p>
        </div>
    </div>

    <p class="trace-muted">
        Sem resposta não significa perda de pacotes.
        Alguns roteadores não respondem às sondagens do Traceroute.
    </p>

    <h3 id="trace-latency-title">Latência por salto</h3>

    <div
        id="trace-latency-chart"
        class="trace-chart"
        role="list"
        aria-labelledby="trace-latency-title"
    >
        <p class="trace-muted">Execute uma consulta para visualizar o gráfico.</p>
    </div>

    <div class="trace-table-wrap">
        <table class="trace-table">
            <thead>
                <tr>
                    <th scope="col">Salto</th>
                    <th scope="col">Endereço IP</th>
                    <th scope="col">Latência</th>
                </tr>
            </thead>
            <tbody id="trace-hops">
                <tr>
                    <td colspan="3" class="trace-muted">
                        Os saltos aparecerão aqui.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <section aria-labelledby="trace-geo-title"
             style="margin-top:24px;">
        <h3 id="trace-geo-title">
            Mapa geográfico dos saltos
        </h3>

        <p class="trace-muted">
            Geolocalização aproximada.
            A posição dos roteadores é estimada por GeoIP
            e não representa o caminho físico comprovado
            dos pacotes.
        </p>

        <p id="trace-geo-status"
           class="trace-muted"
           role="status"
           aria-live="polite">
            Selecione uma consulta concluída para visualizar o mapa.
        </p>

        <div id="trace-geo-map"
             style="height:380px;min-height:300px;
                    border-radius:12px;overflow:hidden;"
             aria-label="Mapa dos saltos geolocalizados">
        </div>
    </section>

</section>

<section class="trace-panel" aria-labelledby="trace-history-title">
    <h2 id="trace-history-title">Histórico recente</h2>

    <button
        id="trace-refresh-history"
        class="trace-button"
        type="button"
        style="margin-bottom: 18px;"
    >
        Atualizar histórico
    </button>

    <div class="trace-table-wrap">
        <table class="trace-table">
            <thead>
                <tr>
                    <th scope="col">Destino</th>
                    <th scope="col">Status</th>
                    <th scope="col">Consulta</th>
                </tr>
            </thead>
            <tbody id="trace-history">
                <tr>
                    <td colspan="3" class="trace-muted">
                        Carregando histórico...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<script type="module">
import { iniciarMapaGeo } from '/js/mapa-interface.js';
import { calcularIndicadoresRota } from "/js/traceroute-indicadores.js";
(() => {
    const mapaGeo = iniciarMapaGeo({
        tipo: 'traceroute',
        elementoId: 'trace-geo-map',
        statusId: 'trace-geo-status'
    });
    'use strict';

    const api = '/api/v1/traceroute/consultas';
    const form = document.getElementById('trace-form');
    const submit = document.getElementById('trace-submit');
    const destino = document.getElementById('trace-destino');
    const status = document.getElementById('trace-status');
    const summary = document.getElementById('trace-summary');
    const hops = document.getElementById('trace-hops');
    const latencyChart = document.getElementById('trace-latency-chart');
    const history = document.getElementById('trace-history');
    const refreshHistory = document.getElementById('trace-refresh-history');

    const respondidos = document.getElementById('trace-respondidos');
    const semResposta = document.getElementById('trace-sem-resposta');
    const destinoAlcancado = document.getElementById('trace-destino-alcancado');
    const latenciaDestino = document.getElementById('trace-latencia-destino');

    let consultaAtual = null;

    function mensagem(texto) {
        status.textContent = texto;
    }

    function limparTabela(corpo, texto) {
        corpo.replaceChildren();

        const linha = document.createElement('tr');
        const celula = document.createElement('td');

        celula.colSpan = 3;
        celula.className = 'trace-muted';
        celula.textContent = texto;

        linha.appendChild(celula);
        corpo.appendChild(linha);
    }

    function adicionarCelula(linha, valor) {
        const celula = document.createElement('td');
        celula.textContent = String(valor);
        linha.appendChild(celula);
    }

    async function requisitar(url, opcoes = {}) {
        const resposta = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                ...opcoes.headers
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

    function renderizarGraficoLatencia(lista) {
        latencyChart.replaceChildren();

        if (lista.length === 0) {
            const vazio = document.createElement('p');
            vazio.className = 'trace-muted';
            vazio.textContent = 'Nenhuma latência disponível.';
            latencyChart.appendChild(vazio);
            return;
        }

        const latencias = lista
            .map(salto => salto.latencia_ms)
            .filter(valor =>
                valor !== null &&
                valor !== undefined &&
                Number.isFinite(Number(valor)) &&
                Number(valor) >= 0
            )
            .map(Number);

        const maiorLatencia = Math.max(0, ...latencias);

        for (const salto of lista) {
            const linha = document.createElement('div');
            linha.className = 'trace-chart-row';
            linha.setAttribute('role', 'listitem');

            const numero = document.createElement('span');
            numero.textContent = `#${salto.salto ?? '-'}`;

            const trilha = document.createElement('div');
            trilha.className = 'trace-chart-track';

            const valor = Number(salto.latencia_ms);
            const valido =
                salto.latencia_ms !== null &&
                salto.latencia_ms !== undefined &&
                Number.isFinite(valor) &&
                valor >= 0;

            const rotulo = document.createElement('span');
            rotulo.className = 'trace-chart-value';

            if (valido) {
                const barra = document.createElement('div');
                barra.className = 'trace-chart-bar';

                const percentual = maiorLatencia > 0
                    ? (valor / maiorLatencia) * 100
                    : 0;

                barra.style.width = `${percentual}%`;
                trilha.appendChild(barra);

                rotulo.textContent = `${valor.toFixed(3)} ms`;
            } else {
                rotulo.textContent = 'Sem resposta';
                rotulo.classList.add('trace-chart-timeout');
            }

            linha.appendChild(numero);
            linha.appendChild(trilha);
            linha.appendChild(rotulo);

            latencyChart.appendChild(linha);
        }
    }

    function atualizarIndicadoresRota(consulta, lista) {
        const indicadores = calcularIndicadoresRota({
            ...consulta,
            saltos: lista
        });

        respondidos.textContent = String(indicadores.respondidos);
        semResposta.textContent = String(indicadores.semResposta);

        destinoAlcancado.textContent = indicadores.destinoAlcancado
            ? 'Sim'
            : 'Não confirmado';

        latenciaDestino.textContent = indicadores.latenciaDestino !== null
            ? `${indicadores.latenciaDestino.toFixed(3)} ms`
            : '—';
    }

    function renderizarSaltos(consulta) {
        const lista = Array.isArray(consulta.saltos)
            ? consulta.saltos
            : [];

        summary.textContent =
            `Destino: ${consulta.destino} | ` +
            `Status: ${consulta.status} | ` +
            `Saltos: ${lista.length}`;

        hops.replaceChildren();
        atualizarIndicadoresRota(consulta, lista);
        renderizarGraficoLatencia(lista);

        if (lista.length === 0) {
            limparTabela(hops, 'Nenhum salto disponível.');
            return;
        }

        for (const salto of lista) {
            const linha = document.createElement('tr');

            adicionarCelula(linha, salto.salto ?? '-');
            adicionarCelula(linha, salto.ip ?? '*');

            const latencia = salto.latencia_ms == null
                ? '*'
                : `${Number(salto.latencia_ms).toFixed(3)} ms`;

            adicionarCelula(linha, latencia);
            hops.appendChild(linha);
        }
    }

    function selecionarConsultaInicial(consultas) {
        if (consultaAtual !== null) {
            return;
        }

        const recente = consultas.find(consulta => consulta.status === 'concluido');

        if (!recente) {
            return;
        }

        void acompanharConsulta(recente.id);
    }

    async function carregarHistorico(selecionarInicial = false) {
        try {
            const consultas = await requisitar(api);
            history.replaceChildren();

            if (!Array.isArray(consultas) || consultas.length === 0) {
                limparTabela(history, 'Nenhuma consulta anterior.');
                return;
            }

            for (const consulta of consultas) {
                const linha = document.createElement('tr');

                adicionarCelula(linha, consulta.destino);
                adicionarCelula(linha, consulta.status);

                const celula = document.createElement('td');
                const botao = document.createElement('button');

                botao.type = 'button';
                botao.className = 'trace-history-button';
                botao.textContent = `Ver consulta #${consulta.id}`;

                botao.addEventListener('click', () => {
                    acompanharConsulta(consulta.id);
                });

                celula.appendChild(botao);
                linha.appendChild(celula);
                history.appendChild(linha);
            }

            if (selecionarInicial) {
                selecionarConsultaInicial(consultas);
            }
        } catch (erro) {
            limparTabela(history, 'Não foi possível carregar o histórico.');
        }
    }

    async function acompanharConsulta(id) {
        mapaGeo.limpar();
        consultaAtual = id;
        mensagem(`Consultando resultado #${id}...`);

        for (let tentativa = 0; tentativa < 30; tentativa++) {
            if (consultaAtual !== id) {
                return;
            }

            try {
                const consulta = await requisitar(`${api}/${id}`);

                if (consultaAtual !== id) {
                    return;
                }

                if (consulta.status === 'concluido') {
                    renderizarSaltos(consulta);
                    mapaGeo.mostrarConsulta(consulta);
                    mensagem(`Consulta #${id} concluída.`);
                    await carregarHistorico();
                    return;
                }

                if (consulta.status === 'erro') {
                    renderizarSaltos(consulta);
                    mensagem(
                        consulta.erro || 'Falha ao executar Traceroute.'
                    );
                    await carregarHistorico();
                    return;
                }

                mensagem(
                    `Consulta #${id}: ${consulta.status}. Aguardando...`
                );
            } catch (erro) {
                mensagem(`Não foi possível consultar o resultado: ${erro.message}`);
                return;
            }

            await new Promise(resolve => setTimeout(resolve, 1500));
        }

        if (consultaAtual === id) {
            mensagem(
                'A consulta continua em processamento. ' +
                'Você pode consultá-la novamente pelo histórico.'
            );
            await carregarHistorico();
        }
    }

    form.addEventListener('submit', async evento => {
        evento.preventDefault();

        const token = document.querySelector(
            'meta[name="csrf-token"]'
        )?.content;

        if (!token) {
            mensagem('Token CSRF indisponível. Atualize a página.');
            return;
        }

        submit.disabled = true;
        mensagem('Enviando consulta...');

        try {
            const consulta = await requisitar(api, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    destino: destino.value.trim()
                })
            });

            await carregarHistorico();
            void acompanharConsulta(consulta.id);
        } catch (erro) {
            mensagem(`Falha ao iniciar consulta: ${erro.message}`);
        } finally {
            submit.disabled = false;
        }
    });

    refreshHistory.addEventListener('click', () => {
        void carregarHistorico();
    });

    void carregarHistorico(true);
    // Liberar o botão após registrar os eventos.
    submit.disabled = false;
})();
</script>

    </div>
</div>

@endsection


@push('styles')
<link rel="stylesheet" href="/vendor/leaflet/leaflet.css">
@endpush

@push('scripts')
<script src="/vendor/leaflet/leaflet.js"></script>
@endpush
