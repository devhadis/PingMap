@extends('layouts.pingmap')

@section('title', 'HTTP/TLS | PingMap')
@section('body_class', 'tools-page')

@push('styles')
<style>
    .http-tls-heading {
        color: #38bdf8;
        font-size: 12px;
        font-weight: bold;
        letter-spacing: 1px;
    }

    .http-tls-title {
        color: #f8fafc;
        font-size: 34px;
        margin: 12px 0;
    }

    .http-tls-description {
        color: #94a3b8;
        line-height: 1.6;
    }

    .http-tls-panel {
        background: #111c2e;
        border: 1px solid #293548;
        border-radius: 14px;
        padding: 24px;
        margin-top: 24px;
    }

    .http-tls-panel h2 {
        color: #f8fafc;
        font-size: 20px;
        margin: 0 0 18px;
    }

    .http-tls-form-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 160px;
        gap: 16px;
        align-items: end;
    }

    .http-tls-field label {
        display: block;
        margin: 0 0 8px;
        color: #cbd5e1;
    }

    .http-tls-field input {
        width: 100%;
        min-height: 46px;
        padding: 11px;
        border: 1px solid #475569;
        border-radius: 8px;
        background: #0b1220;
        color: #f8fafc;
    }

    .http-tls-button {
        width: 100%;
        min-height: 46px;
        margin: 0;
        background: #0284c7;
    }

    .http-tls-button:disabled {
        opacity: .6;
        cursor: wait;
    }

    .http-tls-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .http-tls-stat {
        padding: 18px;
        background: #0b1220;
        border: 1px solid #334155;
        border-radius: 10px;
        min-width: 0;
    }

    .http-tls-stat small {
        display: block;
        color: #94a3b8;
        margin-bottom: 10px;
    }

    .http-tls-stat strong {
        color: #7dd3fc;
        font-size: 20px;
        overflow-wrap: anywhere;
    }

    .http-tls-line {
        display: grid;
        grid-template-columns: 180px minmax(0, 1fr);
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #293548;
        color: #cbd5e1;
    }

    .http-tls-line strong {
        color: #f8fafc;
    }

    .http-tls-line span {
        overflow-wrap: anywhere;
    }

    .http-tls-dominios {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #293548;
    }

    .http-tls-dominios h3 {
        color: #f8fafc;
        font-size: 16px;
        margin: 0 0 12px;
    }

    .http-tls-dominios-lista {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        list-style: none;
        padding: 0;
        margin: 0 0 12px;
    }

    .http-tls-dominios-lista li {
        max-width: 100%;
        padding: 8px 10px;
        border-radius: 6px;
        background: #0b1220;
        border: 1px solid #334155;
        color: #cbd5e1;
        overflow-wrap: anywhere;
    }

    .http-tls-dominios-toggle {
        width: auto;
        min-height: 36px;
        margin-top: 6px;
        padding: 8px 12px;
        border: 1px solid #475569;
        border-radius: 8px;
        background: #0b1220;
        color: #7dd3fc;
        cursor: pointer;
    }

    .http-tls-error {
        color: #fca5a5;
        margin-top: 16px;
    }

    .http-tls-message {
        color: #93c5fd;
        margin-top: 16px;
    }

    [hidden] {
        display: none !important;
    }

    @media (max-width: 700px) {
        .http-tls-form-grid,
        .http-tls-grid,
        .http-tls-line {
            grid-template-columns: 1fr;
        }

        .http-tls-panel {
            padding: 16px;
        }
    }
</style>
@endpush

@section('content')
<div class="tools-shell">
    @include('partials.network_sidebar')

    <main class="tools-content">
        <div class="http-tls-heading">
            NETWORK TOOLS / WEB SECURITY
        </div>

        <h1 class="http-tls-title">HTTP/TLS Analyzer</h1>

        <p class="http-tls-description">
            Analise a conexão HTTPS, o tempo de resposta,
            os cabeçalhos HTTP e o certificado TLS
            de um domínio público.
        </p>

        <section class="http-tls-panel">
            <h2>Nova consulta HTTP/TLS</h2>

            <form
                id="http-tls-form"
                method="POST"
                action="{{ url('/api/v1/http-tls/consultas') }}"
            >
                @csrf

                <div class="http-tls-form-grid">
                    <div class="http-tls-field">
                        <label for="http-tls-dominio">
                            Domínio
                        </label>

                        <input
                            id="http-tls-dominio"
                            name="dominio"
                            type="text"
                            placeholder="example.com"
                            maxlength="253"
                            autocomplete="off"
                            autocapitalize="off"
                            spellcheck="false"
                            required
                        >
                    </div>

                    <button
                        id="http-tls-enviar"
                        class="http-tls-button"
                        type="submit"
                    >
                        Analisar
                    </button>
                </div>
            </form>

            <p
                id="http-tls-mensagem"
                class="http-tls-message"
                role="status"
                aria-live="polite"
            ></p>

            <p
                id="http-tls-erro"
                class="http-tls-error"
                role="alert"
                hidden
            ></p>
        </section>

        <section
            id="http-tls-resultado"
            class="http-tls-panel"
            hidden
        >
            <h2>Resultado da análise</h2>

            <div class="http-tls-grid">
                <div class="http-tls-stat">
                    <small>Status HTTP</small>
                    <strong id="http-tls-status">—</strong>
                </div>

                <div class="http-tls-stat">
                    <small>Tempo de resposta</small>
                    <strong id="http-tls-tempo">—</strong>
                </div>

                <div class="http-tls-stat">
                    <small>IP conectado</small>
                    <strong id="http-tls-ip">—</strong>
                </div>

                <div class="http-tls-stat">
                    <small>Certificado TLS</small>
                    <strong id="http-tls-certificado">—</strong>
                </div>
            </div>

            <section class="http-tls-panel">
                <h2>Detalhes da conexão e certificado</h2>
                <div id="http-tls-detalhes"></div>

                <div
                    id="http-tls-dominios-bloco"
                    class="http-tls-dominios"
                    hidden
                >
                    <h3>Domínios do certificado</h3>

                    <ul
                        id="http-tls-dominios-lista"
                        class="http-tls-dominios-lista"
                    ></ul>

                    <button
                        id="http-tls-dominios-toggle"
                        class="http-tls-dominios-toggle"
                        type="button"
                        aria-controls="http-tls-dominios-lista"
                        aria-expanded="false"
                        hidden
                    >
                        Mostrar todos os domínios
                    </button>
                </div>
            </section>

            <section class="http-tls-panel">
                <h2>Cabeçalhos HTTP</h2>
                <div id="http-tls-cabecalhos"></div>
            </section>
        </section>
    </main>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('http-tls-form');
    const botao = document.getElementById('http-tls-enviar');
    const mensagem = document.getElementById('http-tls-mensagem');
    const aviso = document.getElementById('http-tls-erro');
    const painel = document.getElementById('http-tls-resultado');
    const detalhes = document.getElementById('http-tls-detalhes');
    const cabecalhos = document.getElementById('http-tls-cabecalhos');
    const dominiosBloco = document.getElementById('http-tls-dominios-bloco');
    const dominiosLista = document.getElementById('http-tls-dominios-lista');
    const dominiosToggle = document.getElementById('http-tls-dominios-toggle');

    let dominios = [];
    let expandido = false;

    function preencher(id, valor) {
        document.getElementById(id).textContent =
            valor === null || valor === undefined || valor === ''
                ? 'Não informado'
                : String(valor);
    }

    function adicionarLinha(container, rotulo, valor) {
        const linha = document.createElement('div');
        linha.className = 'http-tls-line';

        const titulo = document.createElement('strong');
        titulo.textContent = rotulo;

        const conteudo = document.createElement('span');
        conteudo.textContent =
            valor === null || valor === undefined || valor === ''
                ? 'Não informado'
                : String(valor);

        linha.append(titulo, conteudo);
        container.appendChild(linha);
    }

    function atualizarDominios() {
        dominiosLista.replaceChildren();

        const visiveis = expandido
            ? dominios
            : dominios.slice(0, 5);

        for (const dominio of visiveis) {
            const item = document.createElement('li');
            item.textContent = dominio;
            dominiosLista.appendChild(item);
        }

        dominiosToggle.hidden = dominios.length <= 5;

        dominiosToggle.textContent = expandido
            ? 'Mostrar menos domínios'
            : 'Mostrar todos os domínios';

        dominiosToggle.setAttribute(
            'aria-expanded',
            String(expandido)
        );
    }

    dominiosToggle.addEventListener('click', () => {
        expandido = !expandido;
        atualizarDominios();
    });

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const dominio = document
            .getElementById('http-tls-dominio')
            .value.trim();

        const token = form.querySelector('[name="_token"]').value;

        botao.disabled = true;
        botao.textContent = 'Consultando...';

        painel.hidden = true;
        dominiosBloco.hidden = true;
        dominiosLista.replaceChildren();
        dominiosToggle.hidden = true;
        dominios = [];
        expandido = false;
        aviso.hidden = true;
        aviso.textContent = '';
        mensagem.textContent = 'Analisando a conexão HTTPS...';

        try {
            const resposta = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ dominio })
            });

            const dados = await resposta.json()
                .catch(() => ({}));

            if (!resposta.ok) {
                const erros = {
                    401: 'Faça login para consultar.',
                    419: 'Sessão expirada. Atualize a página.',
                    422: 'Domínio inválido ou destino não permitido.',
                    429: 'Limite de consultas atingido. Aguarde.',
                    503: 'Não foi possível concluir a consulta HTTP/TLS.'
                };

                throw new Error(
                    erros[resposta.status] ||
                    'Não foi possível realizar a consulta.'
                );
            }

            const resultado = dados.resultado;

            if (
                !resultado ||
                typeof resultado !== 'object' ||
                Array.isArray(resultado)
            ) {
                throw new Error('Resposta HTTP/TLS inesperada.');
            }

            preencher('http-tls-status', resultado.status_http);

            preencher(
                'http-tls-tempo',
                typeof resultado.tempo_ms === 'number'
                    ? resultado.tempo_ms.toLocaleString('pt-BR') + ' ms'
                    : null
            );

            preencher('http-tls-ip', resultado.ip_conectado);

            const certificado =
                resultado.certificado &&
                typeof resultado.certificado === 'object' &&
                !Array.isArray(resultado.certificado)
                    ? resultado.certificado
                    : null;

            let situacao = 'Não disponível';

            if (certificado) {
                if (certificado.expirado === true) {
                    situacao = 'Expirado';
                } else if (certificado.alerta_expiracao === true) {
                    situacao = 'Expira em breve';
                } else {
                    situacao = 'Verificado';
                }
            }

            preencher('http-tls-certificado', situacao);

            detalhes.replaceChildren();
            cabecalhos.replaceChildren();

            adicionarLinha(
                detalhes, 'Domínio', resultado.dominio
            );

            adicionarLinha(
                detalhes,
                'IPs DNS',
                Array.isArray(resultado.ips)
                    ? resultado.ips.join(', ')
                    : null
            );

            if (certificado) {
                const campos = {
                    assunto: 'Assunto',
                    emissor: 'Emissor',
                    inicio_validade: 'Início da validade',
                    fim_validade: 'Fim da validade',
                    dias_restantes: 'Dias restantes',
                    algoritmo_assinatura: 'Algoritmo de assinatura',
                    certificados_na_cadeia: 'Certificados na cadeia'
                };

                for (const [campo, rotulo] of Object.entries(campos)) {
                    adicionarLinha(
                        detalhes,
                        rotulo,
                        certificado[campo]
                    );
                }

                if (Array.isArray(certificado.dominios)) {
                    dominios = certificado.dominios.filter(
                        (dominio) => typeof dominio === 'string'
                    );

                    if (dominios.length > 0) {
                        dominiosBloco.hidden = false;
                        atualizarDominios();
                    }
                }
            } else {
                adicionarLinha(
                    detalhes,
                    'Certificado',
                    'Metadados não disponíveis'
                );
            }

            const headers = resultado.cabecalhos;

            if (
                headers &&
                typeof headers === 'object' &&
                !Array.isArray(headers) &&
                Object.keys(headers).length > 0
            ) {
                for (const [nome, valor] of Object.entries(headers)) {
                    adicionarLinha(cabecalhos, nome, valor);
                }
            } else {
                adicionarLinha(
                    cabecalhos,
                    'Cabeçalhos',
                    'Não informados'
                );
            }

            painel.hidden = false;
            mensagem.textContent = 'Consulta concluída.';

        } catch (erro) {
            aviso.textContent =
                erro instanceof Error
                    ? erro.message
                    : 'Falha inesperada na consulta.';

            aviso.hidden = false;
            mensagem.textContent = '';

        } finally {
            botao.disabled = false;
            botao.textContent = 'Analisar';
        }
    });
});
</script>
@endpush
