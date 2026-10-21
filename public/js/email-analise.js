(() => {
    'use strict';

    const form = document.getElementById('email-form');

    if (!form) {
        return;
    }

    const dominio = document.getElementById('email-dominio');
    const tipo = document.getElementById('email-tipo');
    const seletor = document.getElementById('email-seletor');
    const campoSeletor = document.getElementById('email-seletor-campo');
    const botao = document.getElementById('email-enviar');
    const mensagem = document.getElementById('email-mensagem');
    const painel = document.getElementById('email-resultado');

    const saidaTipo = document.getElementById('email-res-tipo');
    const saidaStatus = document.getElementById('email-res-status');
    const saidaPolitica = document.getElementById('email-res-politica');
    const saidaDominio = document.getElementById('email-res-dominio');
    const saidaRegistros = document.getElementById('email-res-registros');

    function atualizarSeletor() {
        const usarDkim = tipo.value === 'DKIM';

        campoSeletor.hidden = !usarDkim;
        seletor.required = usarDkim;

        if (!usarDkim) {
            seletor.value = '';
        }
    }

    function informar(texto, erro = false) {
        mensagem.textContent = texto;
        mensagem.classList.toggle('error', erro);
    }

    function apresentar(resultado) {
        saidaTipo.textContent = resultado.tipo ?? '—';
        saidaStatus.textContent = resultado.status ?? '—';
        saidaPolitica.textContent = resultado.politica ?? 'Não informada';

        const nome = resultado.dominio ?? dominio.value;
        const quantidade = resultado.quantidade ?? 0;

        saidaDominio.textContent =
            `Domínio: ${nome} | Registros: ${quantidade}`;

        const registros = Array.isArray(resultado.registros)
            ? resultado.registros
            : [];

        saidaRegistros.textContent = registros.length
            ? JSON.stringify(registros, null, 2)
            : 'Nenhum registro correspondente encontrado.';

        painel.hidden = false;
    }

    tipo.addEventListener('change', atualizarSeletor);
    atualizarSeletor();

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        if (!form.reportValidity()) {
            return;
        }

        painel.hidden = true;
        botao.disabled = true;
        informar('Consultando registros DNS...');

        const controlador = new AbortController();
        const limite = setTimeout(() => controlador.abort(), 20000);

        try {
            const dados = {
                dominio: dominio.value.trim(),
                tipo: tipo.value
            };

            if (tipo.value === 'DKIM') {
                dados.seletor = seletor.value.trim();
            }

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
                body: JSON.stringify(dados),
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

            const corpo = await resposta.json();

            if (!resposta.ok) {
                const erro = resposta.status === 422
                    ? 'Verifique o domínio, o tipo e o seletor DKIM.'
                    : 'Não foi possível concluir a consulta DNS.';

                informar(erro, true);
                return;
            }

            if (!corpo.resultado || typeof corpo.resultado !== 'object') {
                throw new Error('Resposta inválida da API.');
            }

            apresentar(corpo.resultado);
            informar('Análise concluída.');
        } catch (erro) {
            informar(
                erro.name === 'AbortError'
                    ? 'A consulta excedeu o tempo de espera.'
                    : 'Erro ao consultar a API. Tente novamente.',
                true
            );
        } finally {
            clearTimeout(limite);
            botao.disabled = false;
        }
    });
})();
