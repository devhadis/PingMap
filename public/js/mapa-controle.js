export function criarControleMapa() {
    let idAtual = null;
    let versaoAtual = 0;

    return {
        consultaAtual() {
            return idAtual;
        },

        selecionarConsulta(id) {
            idAtual = id;
            versaoAtual += 1;

            return versaoAtual;
        },

        respostaEstaAtual(id, versao) {
            return (
                idAtual !== null &&
                idAtual === id &&
                versaoAtual === versao
            );
        },

        limpar() {
            idAtual = null;
            versaoAtual += 1;
        }
    };
}
