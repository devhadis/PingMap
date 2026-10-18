export function prepararTrechosMapa(pontos) {
    if (!Array.isArray(pontos)) {
        return [];
    }

    const trechos = [];

    function valido(ponto) {
        return ponto &&
            Number.isSafeInteger(ponto.salto) &&
            typeof ponto.latitude === 'number' &&
            typeof ponto.longitude === 'number' &&
            Number.isFinite(ponto.latitude) &&
            Number.isFinite(ponto.longitude) &&
            Math.abs(ponto.latitude) <= 90 &&
            Math.abs(ponto.longitude) <= 180;
    }

    for (let i = 1; i < pontos.length; i++) {
        const anterior = pontos[i - 1];
        const atual = pontos[i];

        if (!valido(anterior) || !valido(atual)) {
            continue;
        }

        if (atual.salto !== anterior.salto + 1) {
            continue;
        }

        if (
            anterior.latitude === atual.latitude &&
            anterior.longitude === atual.longitude
        ) {
            continue;
        }

        trechos.push([
            [anterior.latitude, anterior.longitude],
            [atual.latitude, atual.longitude]
        ]);
    }

    return trechos;
}
