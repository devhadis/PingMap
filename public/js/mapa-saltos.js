export function prepararPontosMapa(saltos) {
    if (!Array.isArray(saltos)) {
        return [];
    }

    const pontos = [];

    for (const salto of saltos) {
        if (!salto || typeof salto !== 'object') {
            continue;
        }

        const geo = salto.geo;

        if (!geo || typeof geo !== 'object') {
            continue;
        }

        const latitude = geo.latitude;
        const longitude = geo.longitude;

        if (
            typeof latitude !== 'number' ||
            typeof longitude !== 'number' ||
            !Number.isFinite(latitude) ||
            !Number.isFinite(longitude)
        ) {
            continue;
        }

        if (
            latitude < -90 ||
            latitude > 90 ||
            longitude < -180 ||
            longitude > 180
        ) {
            continue;
        }

        pontos.push({
            salto: salto.salto,
            ip: salto.ip,
            latitude,
            longitude,
            cidade: geo.cidade ?? null,
            pais: geo.pais ?? null,
            geo,
            latencia_ms: salto.latencia_ms ?? salto.media_ms ?? null
        });
    }

    return pontos;
}
