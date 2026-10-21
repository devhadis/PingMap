@extends('layouts.pingmap')

@section('title', 'Email Security Analyzer | PingMap')
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

    .email-heading {
        color: #38bdf8;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .email-title {
        color: #f8fafc;
        font-size: 34px;
        margin: 12px 0;
    }

    .email-description {
        color: #94a3b8;
        line-height: 1.6;
        margin-bottom: 30px;
    }

    .email-panel {
        background: #111c2e;
        border: 1px solid #293548;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 22px;
    }

    .email-panel h2 {
        color: #f8fafc;
        font-size: 20px;
        margin: 0 0 20px;
    }

    .email-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 150px;
        gap: 16px;
        align-items: end;
    }

    .email-field label {
        display: block;
        color: #cbd5e1;
        font-size: 13px;
        margin: 0 0 8px;
    }

    .email-field input,
    .email-field select {
        width: 100%;
        min-height: 46px;
        padding: 11px 12px;
        border: 1px solid #475569;
        border-radius: 8px;
        background: #0b1220;
        color: #f8fafc;
    }

    .email-button {
        min-height: 46px;
        margin-top: 16px;
        background: #0284c7;
        color: #fff;
        border: 0;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
    }

    .email-button:disabled {
        opacity: .6;
        cursor: wait;
    }

    .email-message {
        margin: 18px 0 0;
        color: #93c5fd;
        overflow-wrap: anywhere;
    }

    .email-message.error {
        color: #fca5a5;
    }

    .email-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .email-metric {
        padding: 18px;
        background: #0b1220;
        border: 1px solid #334155;
        border-radius: 10px;
        min-width: 0;
    }

    .email-metric span {
        display: block;
        color: #94a3b8;
        font-size: 12px;
        margin-bottom: 10px;
    }

    .email-metric strong {
        display: block;
        color: #e2e8f0;
        overflow-wrap: anywhere;
    }

    .email-records {
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        color: #cbd5e1;
        background: #0b1220;
        border: 1px solid #334155;
        padding: 16px;
        border-radius: 10px;
        max-height: 420px;
        overflow: auto;
        font-size: 13px;
    }

    .email-note {
        color: #94a3b8;
        font-size: 13px;
        line-height: 1.6;
    }

    [hidden] {
        display: none !important;
    }

    @media (max-width: 850px) {
        .email-form,
        .email-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="tools-shell">
    @include('partials.network_sidebar')

    <main class="tools-content">
        <div class="email-heading">NETWORK TOOLS / EMAIL SECURITY</div>

        <h1 class="email-title">Email Security Analyzer</h1>

        <p class="email-description">
            Consulte os registros SPF, DKIM e DMARC de um domínio
            e identifique suas políticas de autenticação de e-mail.
        </p>

        <section class="email-panel">
            <h2>Nova análise DNS</h2>

            <form id="email-form" data-api-url="/api/v1/email/analises">
                @csrf

                <div class="email-form">
                    <div class="email-field">
                        <label for="email-dominio">Domínio</label>
                        <input
                            id="email-dominio"
                            name="dominio"
                            type="text"
                            placeholder="exemplo.com"
                            maxlength="253"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <div class="email-field">
                        <label for="email-tipo">Tipo de análise</label>
                        <select id="email-tipo" name="tipo">
                            <option value="SPF">SPF</option>
                            <option value="DKIM">DKIM</option>
                            <option value="DMARC">DMARC</option>
                        </select>
                    </div>
                </div>

                <div
                    id="email-seletor-campo"
                    class="email-field"
                    style="margin-top: 16px"
                    hidden
                >
                    <label for="email-seletor">Seletor DKIM</label>
                    <input
                        id="email-seletor"
                        name="seletor"
                        type="text"
                        placeholder="Exemplo: google"
                        maxlength="63"
                        autocomplete="off"
                    >
                </div>

                <button
                    id="email-enviar"
                    class="email-button"
                    type="submit"
                >
                    Executar análise
                </button>
            </form>

            <p
                id="email-mensagem"
                class="email-message"
                role="status"
                aria-live="polite"
            >
                Informe um domínio para iniciar.
            </p>
        </section>

        <section id="email-resultado" class="email-panel" hidden>
            <h2>Resultado da análise</h2>

            <div class="email-summary">
                <div class="email-metric">
                    <span>Tipo</span>
                    <strong id="email-res-tipo">—</strong>
                </div>

                <div class="email-metric">
                    <span>Status</span>
                    <strong id="email-res-status">—</strong>
                </div>

                <div class="email-metric">
                    <span>Política</span>
                    <strong id="email-res-politica">—</strong>
                </div>
            </div>

            <p id="email-res-dominio" class="email-note"></p>

            <h2>Registros encontrados</h2>

            <pre
                id="email-res-registros"
                class="email-records"
            ></pre>

            <p class="email-note">
                A presença de registros DNS não garante que a
                configuração de e-mail esteja totalmente correta.
                A análise DKIM verifica a publicação da chave,
                não a assinatura criptográfica de uma mensagem.
            </p>
        </section>
    </main>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/email-analise.js') }}" defer></script>
@endpush
