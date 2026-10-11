@extends('layouts.pingmap')

@section('title', 'Dashboard | PingMap')

@section('content')
    <h1>PingMap</h1>

    <p>Olá, {{ auth()->user()->name }}!</p>
    <p>Execute um diagnóstico ICMP de um IP público.</p>

    <form id="form-ping">
        @csrf

        <label for="destino">Endereço IP</label>
        <input
            id="destino"
            name="destino"
            value="1.1.1.1"
            required
            maxlength="45"
            placeholder="Ex.: 1.1.1.1"
        >

        <button id="botao-ping" type="submit">
            Executar Ping
        </button>
    </form>

    <p id="mensagem" role="status" aria-live="polite"></p>

    <section id="resultado" hidden>
        <h2>Resultado</h2>
        <p id="status"></p>
        <p id="latencia"></p>
        <p id="perda"></p>
    </section>

    <hr>

    <h2>Meus últimos diagnósticos</h2>

    @forelse($diagnosticos as $diagnostico)
        <p>
            #{{ $diagnostico->id }}
            — {{ $diagnostico->destino }}
            — {{ $diagnostico->status }}
        </p>
    @empty
        <p>Você ainda não possui diagnósticos.</p>
    @endforelse

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Sair da conta</button>
    </form>

    <script>
        const formulario = document.getElementById('form-ping');
        const botao = document.getElementById('botao-ping');
        const mensagem = document.getElementById('mensagem');
        const resultado = document.getElementById('resultado');
        const statusTexto = document.getElementById('status');
        const latencia = document.getElementById('latencia');
        const perda = document.getElementById('perda');

        const endpoint = @json(url('/api/v1/diagnosticos'));
        const csrf = document.querySelector(
            '#form-ping input[name="_token"]'
        ).value;

        const pausa = (ms) =>
            new Promise(resolve => setTimeout(resolve, ms));

        formulario.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            botao.disabled = true;
            resultado.hidden = true;
            mensagem.textContent = 'Enviando diagnóstico...';

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
                    const erro = dados.errors?.destino?.[0]
                        ?? dados.message
                        ?? 'Não foi possível criar o diagnóstico.';
                    throw new Error(erro);
                }

                mensagem.textContent =
                    'Diagnóstico criado. Aguardando o Worker...';

                const urlConsulta = endpoint + '/' + dados.id;
                let concluido = false;

                for (let tentativa = 0; tentativa < 12; tentativa++) {
                    await pausa(2000);

                    const consulta = await fetch(urlConsulta, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!consulta.ok) {
                        throw new Error(
                            'Não foi possível consultar o resultado.'
                        );
                    }

                    const atual = await consulta.json();

                    if (!['pendente', 'executando'].includes(atual.status)) {
                        resultado.hidden = false;

                        statusTexto.textContent =
                            'Status: ' + atual.status;

                        latencia.textContent =
                            'Latência média: ' +
                            (atual.latencia_media_ms ?? 'N/D') +
                            ' ms';

                        perda.textContent =
                            'Perda de pacotes: ' +
                            (atual.perda_percentual ?? 'N/D') +
                            '%';

                        mensagem.textContent =
                            'Diagnóstico finalizado.';

                        concluido = true;
                        break;
                    }
                }

                if (!concluido) {
                    mensagem.textContent =
                        'Ainda processando. Consulte novamente em instantes.';
                }

                if (concluido) {
                    setTimeout(() => window.location.reload(), 3000);
                }
            } catch (erro) {
                mensagem.textContent = erro.message;
            } finally {
                botao.disabled = false;
            }
        });
    </script>
@endsection
