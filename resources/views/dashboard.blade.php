@extends('layouts.pingmap')

@section('title', 'Dashboard | PingMap')
@section('body_class', 'dashboard-page')

@section('content')
<style>
    .dashboard-shell { display: grid; gap: 24px; }
    .pm-header {
        display: flex; justify-content: space-between;
        align-items: center; gap: 16px; flex-wrap: wrap;
    }
    .pm-header h1 { margin: 0; font-size: 32px; }
    .pm-subtitle { color: #94a3b8; margin: 6px 0 0; }
    .pm-panel {
        padding: 22px; background: #0f172a;
        border: 1px solid #334155; border-radius: 14px;
        min-width: 0;
    }
    .pm-panel h2 { margin: 0 0 16px; font-size: 19px; }
    .pm-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }
    .pm-metric {
        background: #243449; border: 1px solid #334155;
        padding: 18px; border-radius: 12px; min-width: 0;
    }
    .pm-metric span {
        display: block; color: #94a3b8; font-size: 13px;
    }
    .pm-metric strong {
        display: block; font-size: 23px;
        margin-top: 10px; overflow-wrap: anywhere;
    }
    .pm-form {
        display: flex; align-items: end; gap: 12px;
        flex-wrap: wrap;
    }
    .pm-form-field { flex: 1 1 260px; }
    .pm-form button { flex: 0 0 180px; }
    .pm-form label { margin-top: 0; }
    .pm-form button, .pm-logout button { margin-top: 0; }
    .pm-logout button {
        width: auto; padding: 11px 20px;
    }
    .pm-status {
        padding: 12px 14px; border-radius: 9px;
        background: #1e293b; margin-top: 16px;
    }
    .pm-status:empty { display: none; }
    .pm-table-wrap { overflow-x: auto; }
    .pm-table {
        width: 100%; border-collapse: collapse;
        text-align: left; font-size: 14px;
    }
    .pm-table th, .pm-table td {
        padding: 13px 10px; border-bottom: 1px solid #334155;
        white-space: nowrap;
    }
    .pm-table th { color: #94a3b8; font-weight: 600; }
    .pm-pill {
        display: inline-block; padding: 5px 9px;
        border-radius: 20px; background: #334155;
    }
    .pm-pill.sucesso { color: #86efac; }
    .pm-pill.sem_resposta, .pm-pill.erro,
    .pm-pill.timeout { color: #fca5a5; }
    .pm-chart {
        width: 100%; height: 230px; display: block;
    }
    .pm-muted { color: #94a3b8; font-size: 13px; }
    .pm-footer {
        display: flex; justify-content: space-between;
        align-items: center; flex-wrap: wrap; gap: 12px;
    }
    @media (max-width: 900px) {
        .pm-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 520px) {
        .pm-grid { grid-template-columns: 1fr; }
        .pm-form button { flex: 1 1 100%; }
        .pm-panel { padding: 15px; }
    }
</style>

<div class="dashboard-shell">
    <header class="pm-header">
        <div>
            <h1>PingMap</h1>
            <p class="pm-subtitle">
                Network Intelligence — Olá, {{ auth()->user()->name }}!
            </p>
        </div>
        <span class="pm-pill sucesso">Diagnóstico de rede</span>
    </header>

    <section class="pm-panel">
        <h2>Executar diagnóstico ICMP</h2>
        <form id="form-ping" class="pm-form">
            @csrf
            <div class="pm-form-field">
                <label for="destino">Endereço IP público</label>
                <input id="destino" name="destino" value="1.1.1.1"
                       maxlength="45" required placeholder="Ex.: 1.1.1.1">
            </div>
            <button id="botao-ping" type="submit">Executar Ping</button>
        </form>
        <p id="mensagem" class="pm-status" role="status"
           aria-live="polite"></p>
    </section>

    <section aria-label="Métricas do último diagnóstico com resposta">
        <h2>Último diagnóstico com resposta</h2>
        <div class="pm-grid">
            <div class="pm-metric">
                <span>Latência média</span>
                <strong id="metrica-media">
                    {{ $ultimoComResposta?->latencia_media_ms !== null
                        ? number_format((float) $ultimoComResposta->latencia_media_ms, 2, ',', '.') . ' ms'
                        : 'N/D' }}
                </strong>
            </div>
            <div class="pm-metric">
                <span>Latência mínima</span>
                <strong id="metrica-minima">
                    {{ $ultimoComResposta?->latencia_minima_ms !== null
                        ? number_format((float) $ultimoComResposta->latencia_minima_ms, 2, ',', '.') . ' ms'
                        : 'N/D' }}
                </strong>
            </div>
            <div class="pm-metric">
                <span>Latência máxima</span>
                <strong id="metrica-maxima">
                    {{ $ultimoComResposta?->latencia_maxima_ms !== null
                        ? number_format((float) $ultimoComResposta->latencia_maxima_ms, 2, ',', '.') . ' ms'
                        : 'N/D' }}
                </strong>
            </div>
            <div class="pm-metric">
                <span>Jitter estimado</span>
                <strong id="metrica-jitter">
                    {{ $ultimoComResposta?->jitter_ms !== null
                        ? number_format((float) $ultimoComResposta->jitter_ms, 2, ',', '.') . ' ms'
                        : 'N/D' }}
                </strong>
            </div>
        </div>
        <p class="pm-muted" id="referencia-metricas">
            @if($ultimoComResposta)
                Referência: diagnóstico #{{ $ultimoComResposta->id }}
                — {{ $ultimoComResposta->destino }}
                — {{ $ultimoComResposta->amostras }} amostra(s)
            @else
                Ainda não existem medições com resposta ICMP.
            @endif
        </p>
    </section>

    <section class="pm-panel">
        <h2>Histórico de latência média</h2>
        <svg id="grafico-latencia" class="pm-chart"
             viewBox="0 0 900 230" role="img"
             aria-label="Gráfico das últimas medições de latência média">
        </svg>
        <p class="pm-muted">
            Cada ponto representa um diagnóstico concluído.
            Não é um monitoramento contínuo.
        </p>
    </section>

    <section class="pm-panel">
        <h2>Meus últimos diagnósticos</h2>
        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Destino</th>
                        <th>Status</th>
                        <th>Média</th>
                        <th>Perda</th>
                        <th>Amostras</th>
                    </tr>
                </thead>
                <tbody id="historico-diagnosticos">
                    @forelse($diagnosticos as $diagnostico)
                        <tr>
                            <td>#{{ $diagnostico->id }}</td>
                            <td>{{ $diagnostico->destino }}</td>
                            <td>
                                <span class="pm-pill {{ $diagnostico->status }}">
                                    {{ $diagnostico->status }}
                                </span>
                            </td>
                            <td>{{ $diagnostico->latencia_media_ms ?? 'N/D' }}</td>
                            <td>{{ $diagnostico->perda_percentual !== null
                                ? $diagnostico->perda_percentual . '%'
                                : 'N/D' }}</td>
                            <td>{{ $diagnostico->amostras ?? 'N/D' }}</td>
                        </tr>
                    @empty
                        <tr id="historico-vazio">
                            <td colspan="6">Você ainda não possui diagnósticos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <footer class="pm-footer">
        <span class="pm-muted">PingMap — Network Intelligence Platform</span>
        <form class="pm-logout" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sair da conta</button>
        </form>
    </footer>
</div>

<script>
(() => {
    const endpoint = @json(url('/api/v1/diagnosticos'));
    const csrf = document.querySelector(
        '#form-ping input[name="_token"]'
    ).value;

    const serie = @json($serieLatencia);
    const formulario = document.getElementById('form-ping');
    const botao = document.getElementById('botao-ping');
    const mensagem = document.getElementById('mensagem');
    const svg = document.getElementById('grafico-latencia');
    const pausa = ms => new Promise(resolve => setTimeout(resolve, ms));

    function formatar(valor) {
        if (valor === null || valor === undefined) return 'N/D';
        const numero = Number(valor);
        return Number.isFinite(numero)
            ? numero.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' ms'
            : 'N/D';
    }

    function desenharGrafico() {
        const ns = 'http://www.w3.org/2000/svg';
        svg.replaceChildren();

        const pontos = serie.filter(
            item => Number.isFinite(Number(item.latencia))
        ).slice(-20);

        if (!pontos.length) {
            const aviso = document.createElementNS(ns, 'text');
            aviso.setAttribute('x', '450');
            aviso.setAttribute('y', '115');
            aviso.setAttribute('text-anchor', 'middle');
            aviso.setAttribute('fill', '#94a3b8');
            aviso.textContent = 'Sem medições de latência disponíveis';
            svg.appendChild(aviso);
            return;
        }

        const valores = pontos.map(item => Number(item.latencia));
        const minimo = Math.min(...valores);
        const maximo = Math.max(...valores);
        const margem = Math.max((maximo - minimo) * 0.2, 1);
        const inferior = Math.max(0, minimo - margem);
        const superior = maximo + margem;
        const faixa = Math.max(superior - inferior, 1);

        const coordenadas = pontos.map((item, indice) => ({
            x: pontos.length === 1
                ? 450 : 55 + indice * 790 / (pontos.length - 1),
            y: 190 - (Number(item.latencia) - inferior) * 150 / faixa,
            id: item.id,
            valor: Number(item.latencia)
        }));

        const linha = document.createElementNS(ns, 'polyline');
        linha.setAttribute('points',
            coordenadas.map(p => `${p.x},${p.y}`).join(' ')
        );
        linha.setAttribute('fill', 'none');
        linha.setAttribute('stroke', '#38bdf8');
        linha.setAttribute('stroke-width', '3');
        linha.setAttribute('stroke-linejoin', 'round');
        svg.appendChild(linha);

        for (const ponto of coordenadas) {
            const circulo = document.createElementNS(ns, 'circle');
            circulo.setAttribute('cx', String(ponto.x));
            circulo.setAttribute('cy', String(ponto.y));
            circulo.setAttribute('r', '5');
            circulo.setAttribute('fill', '#38bdf8');

            const titulo = document.createElementNS(ns, 'title');
            titulo.textContent =
                `#${ponto.id}: ${formatar(ponto.valor)}`;

            circulo.appendChild(titulo);
            svg.appendChild(circulo);
        }
    }

    function atualizarMetricas(dados) {
        if (!(Number(dados.amostras) > 0)) return;

        const campos = {
            'metrica-media': dados.latencia_media_ms,
            'metrica-minima': dados.latencia_minima_ms,
            'metrica-maxima': dados.latencia_maxima_ms,
            'metrica-jitter': dados.jitter_ms
        };

        for (const [id, valor] of Object.entries(campos)) {
            document.getElementById(id).textContent = formatar(valor);
        }

        document.getElementById('referencia-metricas').textContent =
            `Referência: diagnóstico #${dados.id} — ${dados.destino}` +
            ` — ${dados.amostras} amostra(s)`;
    }

    function adicionarHistorico(dados) {
        document.getElementById('historico-vazio')?.remove();

        const tbody = document.getElementById('historico-diagnosticos');
        const linha = document.createElement('tr');
        const valores = [
            `#${dados.id}`,
            dados.destino,
            dados.status,
            dados.latencia_media_ms ?? 'N/D',
            dados.perda_percentual === null
                ? 'N/D' : `${dados.perda_percentual}%`,
            dados.amostras ?? 'N/D'
        ];

        for (const valor of valores) {
            const celula = document.createElement('td');
            celula.textContent = String(valor);
            linha.appendChild(celula);
        }

        tbody.prepend(linha);
        while (tbody.children.length > 20) {
            tbody.lastElementChild.remove();
        }

        if (dados.latencia_media_ms !== null) {
            serie.push({
                id: dados.id,
                destino: dados.destino,
                latencia: Number(dados.latencia_media_ms)
            });
            if (serie.length > 20) serie.shift();
            desenharGrafico();
        }
    }

    formulario.addEventListener('submit', async evento => {
        evento.preventDefault();
        botao.disabled = true;
        mensagem.textContent = 'Criando diagnóstico...';

        try {
            const resposta = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    destino: document.getElementById('destino').value
                })
            });

            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(
                    dados.errors?.destino?.[0]
                    ?? dados.message
                    ?? 'Erro ao criar diagnóstico.'
                );
            }

            mensagem.textContent =
                `Diagnóstico #${dados.id} criado. Aguardando Worker...`;

            let concluido = false;

            for (let tentativa = 0; tentativa < 12; tentativa++) {
                await pausa(2000);

                const consulta = await fetch(
                    endpoint + '/' + dados.id,
                    {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    }
                );

                if (!consulta.ok) {
                    throw new Error('Erro ao consultar diagnóstico.');
                }

                const atual = await consulta.json();

                if (!['pendente', 'executando'].includes(atual.status)) {
                    atualizarMetricas(atual);
                    adicionarHistorico(atual);
                    mensagem.textContent =
                        `Diagnóstico #${atual.id} concluído: ${atual.status}`;
                    concluido = true;
                    break;
                }
            }

            if (!concluido) {
                mensagem.textContent =
                    'O diagnóstico continua processando. Consulte novamente em instantes.';
            }
        } catch (erro) {
            mensagem.textContent = erro.message;
        } finally {
            botao.disabled = false;
        }
    });

    desenharGrafico();
})();
</script>
@endsection
