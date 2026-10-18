@extends('layouts.pingmap')

@section('title', 'MTR | PingMap')
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
        background: transparent;
    }

    .mtr-eyebrow {
        color: #38bdf8;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .mtr-title {
        color: #f8fafc;
        font-size: 34px;
        margin: 12px 0;
    }

    .mtr-description,
    .mtr-muted {
        color: #94a3b8;
    }

    .mtr-description {
        margin-bottom: 28px;
    }

    .mtr-panel {
        background: #111c2e;
        border: 1px solid #293548;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 22px;
    }

    .mtr-panel h2 {
        margin: 0 0 18px;
        color: #f8fafc;
        font-size: 20px;
    }

    .mtr-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 190px;
        gap: 14px;
        align-items: end;
    }

    .mtr-field label {
        display: block;
        margin-bottom: 8px;
        color: #cbd5e1;
    }

    .mtr-field input {
        width: 100%;
        min-height: 46px;
        color: #f8fafc;
        background: #0b1220;
        border: 1px solid #40516a;
        border-radius: 8px;
        padding: 11px;
    }

    .mtr-button {
        min-height: 46px;
        margin-top: 0;
        padding: 11px 16px;
        border: 0;
        border-radius: 8px;
        color: #fff;
        background: #0284c7;
        cursor: pointer;
        font-weight: 700;
    }

    .mtr-button:disabled {
        opacity: .6;
        cursor: wait;
    }

    .mtr-status {
        min-height: 24px;
        margin-top: 18px;
        color: #cbd5e1;
    }

    .mtr-indicators {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        margin: 20px 0;
    }

    .mtr-indicator {
        padding: 18px;
        border: 1px solid #293548;
        border-radius: 10px;
        background: #0b1220;
    }

    .mtr-indicator strong {
        display: block;
        font-size: 13px;
        color: #94a3b8;
    }

    .mtr-indicator p {
        margin: 12px 0 0;
        font-size: 23px;
        color: #f8fafc;
    }

    .mtr-table-wrap {
        overflow-x: auto;
    }

    .mtr-table {
        width: 100%;
        border-collapse: collapse;
        color: #e2e8f0;
        text-align: left;
    }

    .mtr-table th,
    .mtr-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #293548;
        white-space: nowrap;
    }

    .mtr-table th {
        color: #94a3b8;
        font-size: 12px;
    }

    .mtr-chart {
        display: grid;
        gap: 12px;
        margin: 20px 0 28px;
    }

    .mtr-chart-row {
        display: grid;
        grid-template-columns: 55px minmax(0, 1fr) 110px;
        align-items: center;
        gap: 12px;
        font-size: 13px;
    }

    .mtr-chart-track {
        height: 13px;
        background: #0b1220;
        border: 1px solid #293548;
        border-radius: 8px;
        overflow: hidden;
    }

    .mtr-chart-bar {
        height: 100%;
        background: #0ea5e9;
        border-radius: 8px;
    }

    .mtr-chart-value {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .mtr-history-button {
        width: auto;
        margin: 0;
        padding: 4px;
        color: #7dd3fc;
        background: transparent;
        cursor: pointer;
        text-align: left;
        font: inherit;
    }

    @media (max-width: 650px) {
        .mtr-form {
            grid-template-columns: 1fr;
        }

        .mtr-panel {
            padding: 16px;
        }

        .mtr-title {
            font-size: 27px;
        }

        .mtr-chart-row {
            grid-template-columns: 40px minmax(0, 1fr) 85px;
            gap: 7px;
            font-size: 11px;
        }
    }
</style>

<div class="mtr-eyebrow">NETWORK INTELLIGENCE / DIAGNÓSTICO</div>

<h1 class="mtr-title">MTR — Network Analysis</h1>

<p class="mtr-description">
    Analise a rota, a latência e as respostas das sondas
    até um endereço IPv4 público.
</p>

<section class="mtr-panel" aria-labelledby="mtr-form-title">
    <h2 id="mtr-form-title">Nova consulta</h2>

    <form id="mtr-form" class="mtr-form">
        @csrf

        <div class="mtr-field">
            <label for="mtr-destino">Endereço IPv4 público</label>
            <input
                id="mtr-destino"
                name="destino"
                type="text"
                value="1.1.1.1"
                maxlength="45"
                autocomplete="off"
                required
            >
        </div>

        <button id="mtr-submit" class="mtr-button" type="submit">
            Executar MTR
        </button>
    </form>

    <div id="mtr-status" class="mtr-status" role="status" aria-live="polite">
        Informe um IPv4 público para iniciar.
    </div>
</section>

<section class="mtr-panel" aria-labelledby="mtr-results-title">
    <h2 id="mtr-results-title">Resultado do diagnóstico</h2>

    <p id="mtr-summary" class="mtr-muted">
        Nenhuma consulta selecionada.
    </p>

    <div class="mtr-indicators" aria-label="Indicadores MTR">
        <div class="mtr-indicator">
            <strong>Destino alcançado</strong>
            <p id="mtr-destino-alcancado">—</p>
        </div>

        <div class="mtr-indicator">
            <strong>Sem resposta no destino</strong>
            <p id="mtr-perda-destino">—</p>
        </div>

        <div class="mtr-indicator">
            <strong>Latência média do destino</strong>
            <p id="mtr-latencia-media">—</p>
        </div>

        <div class="mtr-indicator">
            <strong>Total de saltos</strong>
            <p id="mtr-total-saltos">—</p>
        </div>
    </div>

    <p class="mtr-muted">
        Saltos intermediários sem resposta não comprovam perda
        de pacotes até o destino. O MTR utiliza uma amostra
        limitada de sondas.
    </p>

    <h3 id="mtr-latency-title">Latência média por salto</h3>

    <div
        id="mtr-latency-chart"
        class="mtr-chart"
        role="list"
        aria-labelledby="mtr-latency-title"
    >
        <p class="mtr-muted">Aguardando consulta.</p>
    </div>

    <div class="mtr-table-wrap">
        <table class="mtr-table">
            <thead>
                <tr>
                    <th scope="col">Salto</th>
                    <th scope="col">Endereço IP</th>
                    <th scope="col">Sondas enviadas</th>
                    <th scope="col">Sem resposta (%)</th>
                    <th scope="col">Última (ms)</th>
                    <th scope="col">Média (ms)</th>
                    <th scope="col">Melhor (ms)</th>
                    <th scope="col">Pior (ms)</th>
                </tr>
            </thead>
            <tbody id="mtr-hops">
                <tr>
                    <td colspan="8">Os saltos aparecerão aqui.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <section aria-labelledby="mtr-geo-title"
             style="margin-top:24px;">
        <h3 id="mtr-geo-title">
            Mapa geográfico dos saltos
        </h3>

        <p class="mtr-muted">
            Geolocalização aproximada.
            A posição dos roteadores é estimada por GeoIP
            e não representa o caminho físico comprovado
            dos pacotes.
        </p>

        <p id="mtr-geo-status"
           class="mtr-muted"
           role="status"
           aria-live="polite">
            Selecione uma consulta concluída para visualizar o mapa.
        </p>

        <div id="mtr-geo-map"
             style="height:380px;min-height:300px;
                    border-radius:12px;overflow:hidden;"
             aria-label="Mapa dos saltos geolocalizados">
        </div>
    </section>

</section>

<section class="mtr-panel" aria-labelledby="mtr-history-title">
    <h2 id="mtr-history-title">Histórico recente</h2>

    <button
        id="mtr-refresh-history"
        class="mtr-button"
        type="button"
        style="margin-bottom:18px"
    >
        Atualizar histórico
    </button>

    <div class="mtr-table-wrap">
        <table class="mtr-table">
            <thead>
                <tr>
                    <th scope="col">Destino</th>
                    <th scope="col">Status</th>
                    <th scope="col">Consulta</th>
                </tr>
            </thead>
            <tbody id="mtr-history">
                <tr>
                    <td colspan="3">Carregando histórico...</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<script type="module">
import { iniciarMapaGeo } from '/js/mapa-interface.js';
import { calcularIndicadoresMtr } from "/js/mtr-indicadores.js";
import { criarControleMtr } from "/js/mtr-controle.js";
(() => {
    const mapaGeo = iniciarMapaGeo({
        tipo: 'mtr',
        elementoId: 'mtr-geo-map',
        statusId: 'mtr-geo-status'
    });
    'use strict';

    const api = '/api/v1/mtr/consultas';
    const form = document.getElementById('mtr-form');
    const submit = document.getElementById('mtr-submit');
    const destino = document.getElementById('mtr-destino');
    const status = document.getElementById('mtr-status');
    const summary = document.getElementById('mtr-summary');
    const hops = document.getElementById('mtr-hops');
    const history = document.getElementById('mtr-history');
    const chart = document.getElementById('mtr-latency-chart');

    const controle = criarControleMtr();

    function mensagem(texto) {
        status.textContent = texto;
    }

    function adicionarCelula(linha, valor) {
        const celula = document.createElement('td');
        celula.textContent = String(valor);
        linha.appendChild(celula);
    }

    function limparTabela(corpo, texto, colunas) {
        corpo.replaceChildren();
        const linha = document.createElement('tr');
        const celula = document.createElement('td');
        celula.colSpan = colunas;
        celula.textContent = texto;
        linha.appendChild(celula);
        corpo.appendChild(linha);
    }

    function numeroValido(valor) {
        return valor !== null &&
            valor !== undefined &&
            valor !== '' &&
            Number.isFinite(Number(valor)) &&
            Number(valor) >= 0;
    }

    function formatarMs(valor) {
        return numeroValido(valor)
            ? Number(valor).toFixed(3)
            : '—';
    }

    async function requisitar(url, opcoes = {}) {
        const resposta = await fetch(url, {
            ...opcoes,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                ...opcoes.headers
            }
        });

        const dados = await resposta.json();

        if (!resposta.ok) {
            throw new Error(dados.message || `Erro HTTP ${resposta.status}`);
        }

        return dados;
    }

    function atualizarIndicadores(consulta, lista) {
        const indicadores = calcularIndicadoresMtr({
            destino: consulta.destino,
            saltos: lista
        });

        document.getElementById('mtr-destino-alcancado')
            .textContent = indicadores.destinoAlcancado
                ? 'Sim'
                : 'Não confirmado';

        document.getElementById('mtr-perda-destino')
            .textContent = indicadores.perdaDestinoPct !== null
                ? `${indicadores.perdaDestinoPct.toFixed(2)}%`
                : '—';

        document.getElementById('mtr-latencia-media')
            .textContent = indicadores.latenciaMediaDestinoMs !== null
                ? `${indicadores.latenciaMediaDestinoMs.toFixed(3)} ms`
                : '—';

        document.getElementById('mtr-total-saltos')
            .textContent = String(indicadores.totalSaltos);
    }

    function renderizarGrafico(lista) {
        chart.replaceChildren();

        if (lista.length === 0) {
            const vazio = document.createElement('p');
            vazio.textContent = 'Nenhuma latência disponível.';
            chart.appendChild(vazio);
            return;
        }

        const valores = lista
            .filter(salto => numeroValido(salto.media_ms))
            .map(salto => Number(salto.media_ms));

        const maior = Math.max(0, ...valores);

        for (const salto of lista) {
            const linha = document.createElement('div');
            linha.className = 'mtr-chart-row';
            linha.setAttribute('role', 'listitem');

            const numero = document.createElement('span');
            numero.textContent = `#${salto.salto ?? '-'}`;

            const trilha = document.createElement('div');
            trilha.className = 'mtr-chart-track';

            const rotulo = document.createElement('span');
            rotulo.className = 'mtr-chart-value';

            if (numeroValido(salto.media_ms)) {
                const barra = document.createElement('div');
                barra.className = 'mtr-chart-bar';
                const percentual = maior > 0
                    ? Number(salto.media_ms) / maior * 100
                    : 0;

                barra.style.width = `${percentual}%`;
                trilha.appendChild(barra);
                rotulo.textContent = `${formatarMs(salto.media_ms)} ms`;
            } else {
                rotulo.textContent = 'Sem resposta';
            }

            linha.append(numero, trilha, rotulo);
            chart.appendChild(linha);
        }
    }

    function renderizarSaltos(consulta) {
        const lista = Array.isArray(consulta.saltos)
            ? consulta.saltos
            : [];

        summary.textContent =
            `Destino: ${consulta.destino} | ` +
            `Status: ${consulta.status} | ` +
            `Saltos: ${lista.length}`;

        atualizarIndicadores(consulta, lista);
        renderizarGrafico(lista);

        hops.replaceChildren();

        if (lista.length === 0) {
            limparTabela(hops, 'Nenhum salto disponível.', 8);
            return;
        }

        for (const salto of lista) {
            const linha = document.createElement('tr');

            adicionarCelula(linha, salto.salto ?? '—');
            adicionarCelula(linha, salto.ip ?? '*');
            adicionarCelula(linha, salto.enviados ?? '—');

            adicionarCelula(
                linha,
                numeroValido(salto.perda_pct)
                    ? Number(salto.perda_pct).toFixed(2)
                    : '—'
            );

            adicionarCelula(linha, formatarMs(salto.ultima_ms));
            adicionarCelula(linha, formatarMs(salto.media_ms));
            adicionarCelula(linha, formatarMs(salto.melhor_ms));
            adicionarCelula(linha, formatarMs(salto.pior_ms));

            hops.appendChild(linha);
        }
    }

    async function carregarHistorico(selecionarInicial = false) {
        const versao = controle.iniciarHistorico();
        try {
            const consultas = await requisitar(api);

            if (!controle.historicoEstaAtual(versao)) return;

            if (!Array.isArray(consultas) || consultas.length === 0) {
                limparTabela(history, 'Nenhuma consulta anterior.', 3);
                return;
            }

            history.replaceChildren();

            for (const consulta of consultas) {
                const linha = document.createElement('tr');

                adicionarCelula(linha, consulta.destino);
                adicionarCelula(linha, consulta.status);

                const celula = document.createElement('td');
                const botao = document.createElement('button');
                botao.type = 'button';
                botao.className = 'mtr-history-button';
                botao.textContent = `Ver consulta #${consulta.id}`;

                botao.addEventListener('click', () => {
                    void acompanharConsulta(consulta.id);
                });

                celula.appendChild(botao);
                linha.appendChild(celula);
                history.appendChild(linha);
            }

            if (selecionarInicial && controle.consultaAtual() === null) {
                const recente = consultas.find(
                    consulta => consulta.status === 'concluido'
                );

                if (recente) {
                    void acompanharConsulta(recente.id);
                }
            }
        } catch {
            if (!controle.historicoEstaAtual(versao)) return;
            limparTabela(history, 'Erro ao carregar histórico.', 3);
        }
    }

    async function acompanharConsulta(id) {
        mapaGeo.limpar();
        controle.selecionarConsulta(id);
        mensagem(`Consultando resultado #${id}...`);

        for (let tentativa = 0; tentativa < 30; tentativa++) {
            if (!controle.consultaEstaAtiva(id)) return;

            try {
                const consulta = await requisitar(`${api}/${id}`);

                if (!controle.consultaEstaAtiva(id)) return;

                if (consulta.status === 'concluido') {
                    renderizarSaltos(consulta);
                    mapaGeo.mostrarConsulta(consulta);
                    mensagem(`Consulta #${id} concluída.`);
                    void carregarHistorico();
                    return;
                }

                if (consulta.status === 'erro') {
                    renderizarSaltos(consulta);
                    mensagem('Não foi possível concluir o diagnóstico.');
                    void carregarHistorico();
                    return;
                }

                mensagem(`Consulta #${id}: ${consulta.status}. Aguardando...`);
            } catch {
                if (controle.consultaEstaAtiva(id)) {
                    mensagem('Erro ao consultar o resultado.');
                }
                return;
            }

            await new Promise(resolve => setTimeout(resolve, 1500));
        }

        if (controle.consultaEstaAtiva(id)) {
            mensagem('A consulta continua em processamento. Atualize o histórico.');
        }
    }

    form.addEventListener('submit', async evento => {
        evento.preventDefault();
        submit.disabled = true;
        mensagem('Enviando diagnóstico...');

        try {
            const token = document.querySelector('meta[name="csrf-token"]');

            const consulta = await requisitar(api, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token?.content ?? ''
                },
                body: JSON.stringify({
                    destino: destino.value.trim()
                })
            });

            void acompanharConsulta(consulta.id);
            void carregarHistorico();
        } catch (erro) {
            mensagem(erro.message || 'Erro ao iniciar consulta.');
        } finally {
            submit.disabled = false;
        }
    });

    document.getElementById('mtr-refresh-history')
        .addEventListener('click', () => {
            void carregarHistorico();
        });

    void carregarHistorico(true);
})();
</script>
@endsection


@push('styles')
<link rel="stylesheet" href="/vendor/leaflet/leaflet.css">
@endpush

@push('scripts')
<script src="/vendor/leaflet/leaflet.js"></script>
@endpush
