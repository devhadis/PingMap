import test from 'node:test';
import assert from 'node:assert/strict';

import {
    criarConteudoPopup
} from '../../public/js/mapa-popup.js';

function documentoFalso() {
    return {
        createElement(tag) {
            return {
                tag,
                textContent: '',
                children: [],
                appendChild(filho) {
                    this.children.push(filho);
                }
            };
        }
    };
}

test('cria popup com os detalhes do roteador', () => {
    const popup = criarConteudoPopup(documentoFalso(), {
        salto: 7,
        ip: '200.255.177.54',
        cidade: 'Rio de Janeiro',
        pais: 'Brazil',
        asn: 'AS4230',
        organizacao: 'CLARO S.A.',
        latencia: '98.973 ms'
    });

    assert.equal(popup.children.length, 7);
    assert.equal(popup.children[0].textContent, 'Salto: 7');
    assert.equal(popup.children[1].textContent, 'IP: 200.255.177.54');
    assert.equal(popup.children[4].textContent, 'ASN: AS4230');
});

test('dados externos permanecem como texto', () => {
    const popup = criarConteudoPopup(documentoFalso(), {
        salto: 1,
        ip: '1.1.1.1',
        cidade: '<script>alert(1)</script>',
        pais: 'Brazil',
        asn: 'AS13335',
        organizacao: 'Cloudflare',
        latencia: '20 ms'
    });

    assert.equal(
        popup.children[2].textContent,
        'Cidade: <script>alert(1)</script>'
    );
});

test('cria popup mesmo com dados ausentes', () => {
    const popup = criarConteudoPopup(documentoFalso(), {});

    assert.equal(popup.children.length, 7);
    assert.equal(
        popup.children[1].textContent,
        'IP: Não disponível'
    );
});
