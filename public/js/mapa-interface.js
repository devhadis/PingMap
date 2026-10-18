import { criarClienteMapa } from './mapa-api.js';
import { prepararPontosMapa } from './mapa-saltos.js';
import { criarRenderizadorMapa } from './mapa-renderizacao.js';
import { prepararTrechosMapa } from './mapa-linhas.js';
import { prepararDetalhesSalto } from './mapa-detalhes.js';
import { criarConteudoPopup } from './mapa-popup.js';

export function iniciarMapaGeo({ tipo, elementoId, statusId }) {
    const elemento = document.getElementById(elementoId);
    const status = document.getElementById(statusId);

    if (!elemento || !status) {
        throw new Error('Elementos do mapa não encontrados.');
    }

    const leaflet = globalThis.L;

    if (!leaflet) {
        status.textContent = 'Biblioteca do mapa indisponível.';
        return {
            limpar() {},
            mostrarConsulta() {}
        };
    }

    const mapa = leaflet.map(elemento).setView([0, 0], 2);

    leaflet.tileLayer(
        'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }
    ).addTo(mapa);

    const renderizador = criarRenderizadorMapa(leaflet, mapa);

    const camadaLinhas = leaflet.layerGroup().addTo(mapa);


    const cliente = criarClienteMapa({
        consultar: async id => {
            const url = `/api/v1/${tipo}/consultas/${id}/geo`;

            const resposta = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json'
                }
            });

            const dados = await resposta.json();

            return {
                codigo: resposta.status,
                dados
            };
        },

        aoEstado: mensagem => {
            status.textContent = mensagem;
        },

        aoConcluir: saltos => {
            const pontos = prepararPontosMapa(saltos);
            const quantidade = renderizador.desenhar(pontos, sobrepostos => {
                const container = document.createElement('div');

                for (const ponto of sobrepostos) {
                    const detalhes = prepararDetalhesSalto(ponto);
                    const conteudo = criarConteudoPopup(document, detalhes);

                    if (container.childNodes.length > 0) {
                        container.appendChild(document.createElement('hr'));
                    }

                    container.appendChild(conteudo);
                }

                return container;
            });

            camadaLinhas.clearLayers();

            const trechos = prepararTrechosMapa(pontos);

            for (const trecho of trechos) {
                leaflet.polyline(trecho, {
                    color: '#1599e6',
                    weight: 3,
                    opacity: 0.8,
                    dashArray: '7, 7',
                    interactive: false
                }).addTo(camadaLinhas);
            }


            mapa.invalidateSize();

            status.textContent = quantidade === 0
                ? 'Nenhum salto possui coordenadas geográficas disponíveis.'
                : `${quantidade} salto(s) geolocalizado(s). ` +
                  'As posições são aproximadas.';
        }
    });

    function limpar() {
        cliente.limpar();
        renderizador.limpar();
        camadaLinhas.clearLayers();

        status.textContent =
            'Aguardando conclusão da consulta selecionada.';
    }

    function mostrarConsulta(consulta) {
        if (
            consulta?.status !== 'concluido' ||
            !Number.isSafeInteger(Number(consulta.id)) ||
            Number(consulta.id) <= 0
        ) {
            return;
        }

        void cliente.selecionar(Number(consulta.id));
    }

    return {
        limpar,
        mostrarConsulta
    };
}
