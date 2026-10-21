import { criarControleMapa } from './mapa-controle.js';

export function criarClienteMapa({
    consultar,
    aguardar = ms => new Promise(resolve => setTimeout(resolve, ms)),
    aoEstado = () => {},
    aoConcluir = () => {}
}) {
    const controle = criarControleMapa();

    const MAX_TENTATIVAS = 8;
    const INTERVALO_MS = 7000;

    async function selecionar(id) {
        const versao = controle.selecionarConsulta(id);

        const ativo = () =>
            controle.respostaEstaAtual(id, versao);

        aoEstado('Consultando geolocalização dos saltos...');

        for (let tentativa = 0; tentativa < MAX_TENTATIVAS; tentativa++) {
            if (!ativo()) {
                return;
            }

            let resposta;

            try {
                resposta = await consultar(id);
            } catch {
                if (ativo()) {
                    aoEstado('Não foi possível consultar a geolocalização.');
                }
                return;
            }

            if (!ativo()) {
                return;
            }

            if (
                resposta?.codigo === 200 &&
                resposta?.dados?.status === 'concluido'
            ) {
                const saltos = resposta.dados.saltos;

                if (!Array.isArray(saltos)) {
                    aoEstado('Resposta de geolocalização inválida.');
                    return;
                }

                aoConcluir(saltos);
                return;
            }

            if (resposta?.codigo === 429) {
                aoEstado(
                    'Limite de consultas atingido. Aguarde antes de tentar novamente.'
                );
                return;
            }

            if (resposta?.codigo === 503) {
                aoEstado('Não foi possível processar a geolocalização.');
                return;
            }

            if (
                resposta?.codigo !== 202 ||
                resposta?.dados?.status !== 'pendente'
            ) {
                aoEstado('Resposta inesperada da geolocalização.');
                return;
            }

            if (tentativa === MAX_TENTATIVAS - 1) {
                break;
            }

            aoEstado('Geolocalização em processamento...');

            await aguardar(INTERVALO_MS);

            if (!ativo()) {
                return;
            }
        }

        if (ativo()) {
            aoEstado(
                'A geolocalização continua em processamento. ' +
                'Selecione novamente a consulta para atualizar.'
            );
        }
    }

    function limpar() {
        controle.limpar();
    }

    return {
        selecionar,
        limpar
    };
}
