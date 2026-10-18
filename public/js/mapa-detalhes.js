function textoOuIndisponivel(valor) {
    if (valor === null || valor === undefined) {
        return 'Não disponível';
    }

    const texto = String(valor).trim();

    return texto || 'Não disponível';
}

export function prepararDetalhesSalto(salto) {
    const dados = salto && typeof salto === 'object'
        ? salto
        : {};

    const geo = dados.geo && typeof dados.geo === 'object'
        ? dados.geo
        : {};

    let asn = 'Não disponível';

    if (
        (typeof geo.asn === 'number' &&
            Number.isSafeInteger(geo.asn) &&
            geo.asn > 0) ||
        (typeof geo.asn === 'string' &&
            /^(AS)?[0-9]+$/i.test(geo.asn.trim()))
    ) {
        const numero = String(geo.asn).trim().replace(/^AS/i, '');
        asn = `AS${numero}`;
    }

    let latencia = 'Não disponível';

    if (
        typeof dados.latencia_ms === 'number' &&
        Number.isFinite(dados.latencia_ms) &&
        dados.latencia_ms >= 0
    ) {
        latencia = `${dados.latencia_ms.toFixed(3)} ms`;
    }

    return {
        salto: dados.salto ?? null,
        ip: textoOuIndisponivel(dados.ip),
        cidade: textoOuIndisponivel(geo.cidade),
        pais: textoOuIndisponivel(geo.pais),
        asn,
        organizacao: textoOuIndisponivel(geo.organizacao),
        latencia
    };
}
