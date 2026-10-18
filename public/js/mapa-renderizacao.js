export function criarRenderizadorMapa(leaflet, mapa) {
    const grupo = leaflet.layerGroup().addTo(mapa);

    function coordenadasValidas(ponto) {
        if (!ponto || typeof ponto !== 'object') {
            return false;
        }

        const lat = ponto.latitude;
        const lon = ponto.longitude;

        return (
            typeof lat === 'number' &&
            typeof lon === 'number' &&
            Number.isFinite(lat) &&
            Number.isFinite(lon) &&
            lat >= -90 &&
            lat <= 90 &&
            lon >= -180 &&
            lon <= 180
        );
    }

    function desenhar(pontos, criarPopup = null) {
        grupo.clearLayers();

        const coordenadas = [];

        if (!Array.isArray(pontos)) {
            return 0;
        }

        const validos = pontos.filter(coordenadasValidas);

        for (const ponto of validos) {
            const posicao = [
                ponto.latitude,
                ponto.longitude
            ];

            const marcador = leaflet.circleMarker(posicao);

            if (typeof criarPopup === 'function') {
                const sobrepostos = validos.filter(outro =>
                    outro.latitude === ponto.latitude &&
                    outro.longitude === ponto.longitude
                );

                const conteudo = criarPopup(sobrepostos);

                if (
                    conteudo !== null &&
                    conteudo !== undefined
                ) {
                    marcador.bindPopup(conteudo);
                }
            }

            marcador.addTo(grupo);
            coordenadas.push(posicao);
        }

        if (coordenadas.length > 1) {
            mapa.fitBounds(
                leaflet.latLngBounds(coordenadas)
            );
        } else if (coordenadas.length === 1) {
            mapa.setView(coordenadas[0], 5);
        }

        return coordenadas.length;
    }

    function limpar() {
        grupo.clearLayers();
    }

    return {
        desenhar,
        limpar
    };
}
