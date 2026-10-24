<style>
    .bgp-rede-lista {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }

    .bgp-rede-lista .bgp-card h3 {
        color: #f8fafc;
        font-size: 16px;
        margin: 0 0 18px;
        overflow-wrap: anywhere;
    }

    .bgp-rede-lista .bgp-card span {
        display: block;
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 14px;
    }
</style>

<section class="bgp-panel">
    <h2>Consulta BGP por IP, CIDR ou domínio</h2>

    <p class="bgp-description">
        Informe um IPv4, IPv6, prefixo CIDR ou domínio público
        para consultar informações de roteamento BGP.
    </p>

    <form
        id="bgp-rede-form"
        method="POST"
        action="{{ url('/api/v1/bgp/rede/consultas') }}"
    >
        @csrf

        <div class="bgp-form-grid">
            <div class="bgp-field">
                <label for="bgp-rede-alvo">
                    Endereço IP, rede CIDR ou domínio
                </label>

                <input
                    id="bgp-rede-alvo"
                    name="alvo"
                    type="text"
                    placeholder="Ex.: dns.google ou 8.8.8.8"
                    maxlength="253"
                    autocomplete="off"
                    spellcheck="false"
                    required
                >
            </div>

            <button
                id="bgp-rede-enviar"
                class="bgp-button"
                type="submit"
            >
                Consultar rede
            </button>
        </div>
    </form>

    <p
        id="bgp-rede-mensagem"
        class="bgp-message"
        role="status"
        aria-live="polite"
    ></p>
</section>

<section
    id="bgp-rede-resultados"
    class="bgp-panel"
    hidden
>
    <h2>Resultado da consulta por rede</h2>

    <div
        id="bgp-rede-resumo"
        class="bgp-summary"
    ></div>

    <div
        id="bgp-rede-lista"
        class="bgp-rede-lista"
    ></div>
</section>

<script data-bgp-rede-integration>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('bgp-rede-form');
    const botao = document.getElementById('bgp-rede-enviar');
    const mensagem = document.getElementById('bgp-rede-mensagem');
    const painel = document.getElementById('bgp-rede-resultados');
    const resumo = document.getElementById('bgp-rede-resumo');
    const lista = document.getElementById('bgp-rede-lista');

    function adicionarDetalhe(destino, titulo, valor) {
        const rotulo = document.createElement('strong');
        const texto = document.createElement('span');

        rotulo.textContent = titulo;
        texto.textContent = String(valor);

        destino.append(rotulo, texto);
    }

    function adicionarResumo(titulo, valor) {
        const card = document.createElement('div');
        card.className = 'bgp-card';

        adicionarDetalhe(card, titulo, valor);
        resumo.appendChild(card);
    }

    function formatarAsns(asns) {
        if (asns.length === 0) {
            return 'Nenhum ASN encontrado';
        }

        return asns
            .map(asn => 'AS' + String(asn))
            .join(', ');
    }

    function validarResultadoIp(resultado) {
        if (
            !resultado ||
            typeof resultado !== 'object' ||
            Array.isArray(resultado) ||
            typeof resultado.consulta !== 'string' ||
            typeof resultado.anunciado !== 'boolean' ||
            !Array.isArray(resultado.asns) ||
            (
                resultado.prefixo !== null &&
                typeof resultado.prefixo !== 'string'
            ) ||
            typeof resultado.coberto_por_prefixo_maior !== 'boolean'
        ) {
            throw new Error('Resposta BGP inesperada.');
        }
    }

    function mostrarDetalhesIp(resultado, destino) {
        validarResultadoIp(resultado);

        adicionarDetalhe(
            destino,
            'Prefixo BGP',
            resultado.prefixo || 'Não encontrado'
        );

        adicionarDetalhe(
            destino,
            'Estado BGP',
            resultado.anunciado
                ? 'Anunciado'
                : 'Não anunciado'
        );

        adicionarDetalhe(
            destino,
            'ASNs de origem',
            formatarAsns(resultado.asns)
        );

        adicionarDetalhe(
            destino,
            'Coberto por prefixo maior',
            resultado.coberto_por_prefixo_maior
                ? 'Sim'
                : 'Não'
        );
    }

    function mostrarResultado(resultado) {
        if (
            !resultado ||
            typeof resultado !== 'object' ||
            Array.isArray(resultado)
        ) {
            throw new Error('Resposta BGP inesperada.');
        }

        resumo.replaceChildren();
        lista.replaceChildren();

        if (resultado.tipo === 'dominio') {
            if (
                typeof resultado.consulta !== 'string' ||
                !Array.isArray(resultado.resultados) ||
                resultado.resultados.length > 8
            ) {
                throw new Error('Resposta de domínio inesperada.');
            }

            adicionarResumo('Domínio', resultado.consulta);

            adicionarResumo(
                'IPs analisados',
                resultado.resultados.length
            );

            for (const ip of resultado.resultados) {
                validarResultadoIp(ip);

                const card = document.createElement('article');
                card.className = 'bgp-card';

                const titulo = document.createElement('h3');
                titulo.textContent = ip.consulta;

                card.appendChild(titulo);

                mostrarDetalhesIp(ip, card);

                lista.appendChild(card);
            }

            return;
        }

        if (
            resultado.tipo !== 'ip' &&
            resultado.tipo !== 'cidr'
        ) {
            throw new Error('Tipo de resultado BGP desconhecido.');
        }

        validarResultadoIp(resultado);

        adicionarResumo(
            'Tipo de consulta',
            resultado.tipo === 'cidr'
                ? 'Rede CIDR'
                : 'Endereço IP'
        );

        adicionarResumo('Alvo', resultado.consulta);

        adicionarResumo(
            'Prefixo BGP',
            resultado.prefixo || 'Não encontrado'
        );

        adicionarResumo(
            'Estado BGP',
            resultado.anunciado
                ? 'Anunciado'
                : 'Não anunciado'
        );

        adicionarResumo(
            'ASNs de origem',
            formatarAsns(resultado.asns)
        );

        adicionarResumo(
            'Coberto por prefixo maior',
            resultado.coberto_por_prefixo_maior
                ? 'Sim'
                : 'Não'
        );
    }

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const alvo = document.getElementById('bgp-rede-alvo')
            .value.trim();

        const token = form.querySelector(
            '[name="_token"]'
        ).value;

        botao.disabled = true;
        painel.hidden = true;

        resumo.replaceChildren();
        lista.replaceChildren();

        mensagem.classList.remove('error');
        mensagem.textContent = 'Consultando informações BGP...';

        try {
            const resposta = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ alvo })
            });

            const conteudo = await resposta.json()
                .catch(() => ({}));

            if (!resposta.ok) {
                const erros = {
                    401: 'Faça login para consultar.',
                    403: 'Acesso não autorizado.',
                    419: 'Sessão expirada. Atualize a página.',
                    422: 'Informe um IP, CIDR ou domínio público válido.',
                    429: 'Limite de consultas atingido. Aguarde.',
                    503: 'Serviço BGP indisponível no momento.'
                };

                throw new Error(
                    erros[resposta.status] ||
                    'Não foi possível realizar a consulta.'
                );
            }

            mostrarResultado(conteudo.resultado);

            painel.hidden = false;
            mensagem.textContent = 'Consulta concluída.';

        } catch (erro) {
            painel.hidden = true;
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
