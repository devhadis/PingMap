(() => {
    'use strict';

    const form = document.getElementById('dnssec-form');

    if (!form) {
        return;
    }

    const dominio = document.getElementById('dnssec-dominio');
    const botao = document.getElementById('dnssec-enviar');
    const mensagem = document.getElementById('dnssec-mensagem');
    const painel = document.getElementById('dnssec-resultado');

    const saidaDominio = document.getElementById('dnssec-res-dominio');
    const saidaTipo = document.getElementById('dnssec-res-tipo');
    const saidaEstado = document.getElementById('dnssec-res-estado');
    const explicacao = document.getElementById('dnssec-explicacao');

    const estados = {
        seguro: {
            titulo: 'Seguro',
            descricao:
                'A resposta DNS foi validada criptograficamente ' +
                'pelo resolvedor DNSSEC.'
        },
        inseguro: {
            titulo: 'Inseguro',
            descricao:
                'A resposta foi identificada como não assinada. ' +
                'Isso não significa necessariamente um ataque.'
        },
        indeterminado: {
            titulo: 'Indeterminado',
            descricao:
                'Não foi possível concluir a validação DNSSEC. ' +
                'Pode ter ocorrido uma falha de resolução.'
        }
    };

    function informar(texto, erro = false) {
        mensagem.textContent = texto;
        mensagem.classList.toggle('error', erro);
    }

    function apresentar(resultado) {
        if (!resultado || typeof resultado !== 'object') {
            throw new Error('Resposta inválida da API.');
        }

        const estado = resultado.estado;

        if (
            typeof estado !== 'string' ||
            !Object.prototype.hasOwnProperty.call(estados, estado)
        ) {
            throw new Error('Estado DNSSEC inesperado.');
        }

        if (
            typeof resultado.dominio !== 'string' ||
            typeof resultado.tipo !== 'string' ||
            resultado.tipo !== 'A'
        ) {
            throw new Error('Dados DNSSEC inválidos.');
        }

        saidaDominio.textContent = resultado.dominio;
        saidaTipo.textContent = resultado.tipo;
        saidaEstado.textContent = estados[estado].titulo;
        explicacao.textContent = estados[estado].descricao;

        painel.hidden = false;
    }

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        if (botao.disabled || !form.reportValidity()) {
            return;
        }

        painel.hidden = true;
        botao.disabled = true;

        informar('Validando DNSSEC. Aguarde...');

        const controlador = new AbortController();
        const limite = setTimeout(
            () => controlador.abort(),
            20000
        );

        try {
            const token = document.querySelector(
                'meta[name="csrf-token"]'
            )?.content;

            if (!token) {
                throw new Error('Token de segurança indisponível.');
            }

            const resposta = await fetch(form.dataset.apiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    dominio: dominio.value.trim()
                }),
                signal: controlador.signal
            });

            if (resposta.status === 401 || resposta.status === 419) {
                informar(
                    'Sua sessão expirou. Faça login novamente.',
                    true
                );
                return;
            }

            if (resposta.status === 429) {
                informar(
                    'Limite de consultas atingido. Aguarde um minuto.',
                    true
                );
                return;
            }

            if (resposta.status === 422) {
                informar(
                    'Domínio inválido. Verifique o endereço informado.',
                    true
                );
                return;
            }

            if (!resposta.ok) {
                informar(
                    'Não foi possível concluir a validação DNSSEC.',
                    true
                );
                return;
            }

            const corpo = await resposta.json();

            apresentar(corpo.resultado);

            informar('Análise DNSSEC concluída.');
        } catch (erro) {
            painel.hidden = true;

            if (erro.name === 'AbortError') {
                informar(
                    'A análise excedeu o tempo de espera.',
                    true
                );
            } else {
                informar(
                    'Erro ou resposta inválida da API DNSSEC.',
                    true
                );
            }
        } finally {
            clearTimeout(limite);
            botao.disabled = false;
        }
    });
})();
