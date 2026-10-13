export function calcularIndicadoresRota(consulta) {
    const saltos = Array.isArray(consulta.saltos)
        ? consulta.saltos
        : [];

    function respondeu(salto) {
        if (!salto || typeof salto.ip !== 'string' || !salto.ip) {
            return false;
        }

        if (salto.latencia_ms === null ||
            salto.latencia_ms === undefined ||
            salto.latencia_ms === '') {
            return false;
        }

        const latencia = Number(salto.latencia_ms);

        return Number.isFinite(latencia) && latencia >= 0;
    }

    const respondidos = saltos.filter(respondeu).length;

    const semResposta = saltos.length - respondidos;

    const destino = saltos.find(salto =>
        salto?.ip === consulta.destino && respondeu(salto)
    );

    return {
        respondidos,
        semResposta,
        destinoAlcancado: Boolean(destino),
        latenciaDestino: destino
            ? Number(destino.latencia_ms)
            : null
    };
}
