function numeroValido(valor, minimo = 0, maximo = Infinity) {
    if (
        valor === null ||
        valor === undefined ||
        valor === '' ||
        typeof valor === 'boolean'
    ) {
        return false;
    }

    const numero = Number(valor);

    return Number.isFinite(numero) &&
        numero >= minimo &&
        numero <= maximo;
}

export function calcularIndicadoresMtr(consulta) {
    const saltos = Array.isArray(consulta?.saltos)
        ? consulta.saltos
        : [];

    const destino = consulta?.destino;

    const saltoDestino = saltos.find(
        salto => salto &&
            typeof destino === 'string' &&
            salto.ip === destino
    );

    if (!saltoDestino) {
        return {
            destinoAlcancado: false,
            perdaDestinoPct: null,
            latenciaMediaDestinoMs: null,
            totalSaltos: saltos.length
        };
    }

    const perdaValida = numeroValido(
        saltoDestino.perda_pct,
        0,
        100
    );

    const perda = perdaValida
        ? Number(saltoDestino.perda_pct)
        : null;

    const mediaValida = numeroValido(
        saltoDestino.media_ms
    );

    const media = mediaValida
        ? Number(saltoDestino.media_ms)
        : null;

    const enviadosValidos = numeroValido(
        saltoDestino.enviados,
        1
    );

    const destinoAlcancado =
        enviadosValidos &&
        perda !== null &&
        perda < 100 &&
        media !== null;

    return {
        destinoAlcancado,
        perdaDestinoPct: perda,
        latenciaMediaDestinoMs: media,
        totalSaltos: saltos.length
    };
}
