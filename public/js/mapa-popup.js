export function criarConteudoPopup(documento, detalhes = {}) {
    const container = documento.createElement('div');

    const campos = [
        ['Salto', detalhes.salto],
        ['IP', detalhes.ip],
        ['Cidade', detalhes.cidade],
        ['País', detalhes.pais],
        ['ASN', detalhes.asn],
        ['Organização', detalhes.organizacao],
        ['Latência', detalhes.latencia]
    ];

    for (const [rotulo, valor] of campos) {
        const linha = documento.createElement('div');

        const texto = valor === null ||
            valor === undefined ||
            String(valor).trim() === ''
                ? 'Não disponível'
                : String(valor);

        linha.textContent = `${rotulo}: ${texto}`;

        container.appendChild(linha);
    }

    return container;
}
