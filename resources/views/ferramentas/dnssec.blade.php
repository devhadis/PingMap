@extends('layouts.pingmap')

@section('title', 'DNSSEC Analyzer | PingMap')
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

    .dnssec-heading {
        color: #38bdf8;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .dnssec-title {
        color: #f8fafc;
        font-size: 34px;
        margin: 12px 0;
    }

    .dnssec-description {
        color: #94a3b8;
        line-height: 1.6;
        margin-bottom: 30px;
    }

    .dnssec-panel {
        background: #111c2e;
        border: 1px solid #293548;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 22px;
    }

    .dnssec-panel h2 {
        color: #f8fafc;
        font-size: 20px;
        margin: 0 0 20px;
    }

    .dnssec-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 150px;
        gap: 16px;
        align-items: end;
    }

    .dnssec-field label {
        display: block;
        color: #cbd5e1;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .dnssec-field input {
        box-sizing: border-box;
        width: 100%;
        min-height: 46px;
        padding: 11px 12px;
        border: 1px solid #475569;
        border-radius: 8px;
        background: #0b1220;
        color: #f8fafc;
    }

    .dnssec-button {
        min-height: 46px;
        background: #0284c7;
        color: white;
        border: 0;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
    }

    .dnssec-button:disabled {
        opacity: .6;
        cursor: wait;
    }

    .dnssec-message {
        margin-top: 18px;
        color: #93c5fd;
        overflow-wrap: anywhere;
    }

    .dnssec-message.error {
        color: #fca5a5;
    }

    .dnssec-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .dnssec-metric {
        background: #0b1220;
        border: 1px solid #334155;
        border-radius: 10px;
        padding: 18px;
        min-width: 0;
    }

    .dnssec-metric span {
        display: block;
        color: #94a3b8;
        font-size: 12px;
        margin-bottom: 10px;
    }

    .dnssec-metric strong {
        display: block;
        color: #f8fafc;
        overflow-wrap: anywhere;
    }

    .dnssec-note {
        color: #94a3b8;
        font-size: 13px;
        line-height: 1.7;
    }

    [hidden] {
        display: none !important;
    }

    @media (max-width: 850px) {
        .dnssec-form,
        .dnssec-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="tools-shell">
    @include('partials.network_sidebar')

    <main class="tools-content">
        <div class="dnssec-heading">
            NETWORK TOOLS / DNS SECURITY
        </div>

        <h1 class="dnssec-title">DNSSEC Analyzer</h1>

        <p class="dnssec-description">
            Verifique se uma resposta DNS possui validação
            criptográfica DNSSEC.
        </p>

        <section class="dnssec-panel">
            <h2>Nova análise DNSSEC</h2>

            <form
                id="dnssec-form"
                data-api-url="/api/v1/dnssec/analises"
            >
                @csrf

                <div class="dnssec-form">
                    <div class="dnssec-field">
                        <label for="dnssec-dominio">Domínio</label>

                        <input
                            id="dnssec-dominio"
                            name="dominio"
                            type="text"
                            placeholder="cloudflare.com"
                            maxlength="253"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <button
                        id="dnssec-enviar"
                        class="dnssec-button"
                        type="submit"
                    >
                        Analisar
                    </button>
                </div>
            </form>

            <p
                id="dnssec-mensagem"
                class="dnssec-message"
                role="status"
                aria-live="polite"
            ></p>
        </section>

        <section
            id="dnssec-resultado"
            class="dnssec-panel"
            hidden
        >
            <h2>Resultado da análise</h2>

            <div class="dnssec-summary">
                <div class="dnssec-metric">
                    <span>Domínio</span>
                    <strong id="dnssec-res-dominio">—</strong>
                </div>

                <div class="dnssec-metric">
                    <span>Tipo consultado</span>
                    <strong id="dnssec-res-tipo">—</strong>
                </div>

                <div class="dnssec-metric">
                    <span>Estado DNSSEC</span>
                    <strong id="dnssec-res-estado">—</strong>
                </div>
            </div>

            <p
                id="dnssec-explicacao"
                class="dnssec-note"
                aria-live="polite"
            ></p>
        </section>

        <section class="dnssec-panel">
            <h2>Como interpretar os resultados</h2>

            <p class="dnssec-note">
                <strong>Seguro:</strong>
                a resposta foi validada pelo resolvedor DNSSEC.
            </p>

            <p class="dnssec-note">
                <strong>Inseguro:</strong>
                a resposta foi identificada como não assinada.
                Isso não significa necessariamente um ataque.
            </p>

            <p class="dnssec-note">
                <strong>Indeterminado:</strong>
                não foi possível concluir a validação.
                Isso pode ocorrer por falhas de resolução
                ou ausência de evidências suficientes.
            </p>

            <p class="dnssec-note">
                Esta versão consulta registros A.
                O resultado não representa uma auditoria
                completa de todos os registros do domínio.
            </p>
        </section>
    </main>
</div>

<script src="{{ asset('js/dnssec-analise.js') }}" defer></script>
@endsection
