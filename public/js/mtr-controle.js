export function criarControleMtr() {
    let idAtual = null;
    let versaoHistorico = 0;

    return {
        consultaAtual() {
            return idAtual;
        },

        selecionarConsulta(id) {
            idAtual = id;
        },

        consultaEstaAtiva(id) {
            return idAtual !== null && idAtual === id;
        },

        iniciarHistorico() {
            versaoHistorico += 1;
            return versaoHistorico;
        },

        historicoEstaAtual(versao) {
            return versao === versaoHistorico;
        }
    };
}
