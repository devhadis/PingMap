@extends('layouts.pingmap')

@section('title', 'RDAP Lookup | PingMap')
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

    .rdap-heading {
        color: #38bdf8;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .rdap-title {
        color: #f8fafc;
        font-size: 34px;
        margin: 12px 0;
    }

    .rdap-description {
        color: #94a3b8;
        line-height: 1.6;
    }

    .rdap-panel {
        background: #111c2e;
        border: 1px solid #293548;
        border-radius: 14px;
        padding: 24px;
        margin-top: 28px;
    }

    .rdap-panel h2 {
        color: #f8fafc;
        font-size: 20px;
        margin: 0 0 20px;
    }

    .rdap-form-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 160px;
        gap: 16px;
        align-items: end;
    }

    .rdap-field label {
        display: block;
        color: #cbd5e1;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .rdap-field input {
        box-sizing: border-box;
        width: 100%;
        min-height: 46px;
        padding: 11px 12px;
        border: 1px solid #475569;
        border-radius: 8px;
        background: #0b1220;
        color: #f8fafc;
    }

    .rdap-button {
        min-height: 46px;
        padding: 10px;
        background: #0284c7;
        color: white;
        border: 0;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
    }

    .rdap-button:disabled {
        opacity: .6;
        cursor: wait;
    }

    .rdap-message {
        color: #93c5fd;
        margin-top: 18px;
        overflow-wrap: anywhere;
    }

    .rdap-message.error {
        color: #fca5a5;
    }

    #rdap-dados {
        color: #cbd5e1;
        overflow-wrap: anywhere;
    }

    [hidden] {
        display: none !important;
    }

    @media (max-width: 850px) {
        .rdap-form-grid {
            grid-template-columns: 1fr;
        }
    }

</style>

<div class="tools-shell">
    @include('partials.network_sidebar')

    <main class="tools-content">
        <div class="rdap-heading">
            NETWORK TOOLS / IP INFORMATION
        </div>

        <h1 class="rdap-title">RDAP Lookup</h1>

        <p class="rdap-description">
            Consulte informações de registro de endereços
            IPv4 e IPv6 utilizando o protocolo RDAP.
        </p>

        <section class="rdap-panel">
            <h2>Nova consulta RDAP</h2>

            <form id="rdap-form" method="POST" action="{{ url('/api/v1/rdap/consultas') }}">
                @csrf

                <div class="rdap-form-grid">
                    <div class="rdap-field">
                        <label for="rdap-ip">
                            Endereço IP
                        </label>

                        <input
                            id="rdap-ip"
                            name="ip"
                            type="text"
                            placeholder="8.8.8.8"
                            maxlength="45"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <button
                        id="rdap-enviar"
                        class="rdap-button"
                        type="submit"
                    >
                        Consultar RDAP
                    </button>
                </div>
            </form>

            <p
                id="rdap-mensagem"
                class="rdap-message"
                role="status"
                aria-live="polite"
            ></p>
        </section>

        <section
            id="rdap-resultado"
            class="rdap-panel"
            hidden
        >
            <h2>Resultado da consulta</h2>
            <div id="rdap-dados"></div>
        </section>

    </main>
</div>

<script data-rdap-integration>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('rdap-form');
    const botao = document.getElementById('rdap-enviar');
    const mensagem = document.getElementById('rdap-mensagem');
    const painel = document.getElementById('rdap-resultado');
    const dados = document.getElementById('rdap-dados');

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const ip = document.getElementById('rdap-ip').value.trim();
        const token = form.querySelector('[name="_token"]').value;

        botao.disabled = true;
        painel.hidden = true;
        dados.replaceChildren();
        mensagem.classList.remove('error');
        mensagem.textContent = 'Consultando informações RDAP...';

        try {
            const resposta = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ ip })
            });

            const conteudo = await resposta.json()
                .catch(() => ({}));

            if (!resposta.ok) {
                const erros = {
                    401: 'Faça login para consultar.',
                    419: 'Sessão expirada. Atualize a página.',
                    422: 'Endereço IP inválido ou não público.',
                    429: 'Limite de consultas atingido. Aguarde.',
                    503: 'Serviço RDAP indisponível no momento.'
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
                Array.isArray(resultado)
            ) {
                throw new Error('Resposta RDAP inesperada.');
            }

            const campos = {
                handle: 'Identificador',
                nome: 'Nome da rede',
                inicio: 'IP inicial',
                fim: 'IP final',
                versao: 'Versão IP',
                pais: 'País'
            };

            for (const [chave, titulo] of Object.entries(campos)) {
                const linha = document.createElement('p');
                const rotulo = document.createElement('strong');
                const valor = document.createElement('span');

                rotulo.textContent = titulo + ': ';

                const informacao = resultado[chave];

                valor.textContent =
                    typeof informacao === 'string' &&
                    informacao.trim() !== ''
                        ? informacao
                        : 'Não informado';

                linha.append(rotulo, valor);
                dados.appendChild(linha);
            }

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
