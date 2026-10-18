import test from 'node:test';
import assert from 'node:assert/strict';

import {
    criarControleMapa
} from '../../public/js/mapa-controle.js';

test('inicia sem consulta selecionada', () => {
    const controle = criarControleMapa();

    assert.equal(controle.consultaAtual(), null);
});

test('seleciona consulta e autoriza sua resposta', () => {
    const controle = criarControleMapa();

    const versao = controle.selecionarConsulta(4);

    assert.equal(controle.consultaAtual(), 4);
    assert.equal(controle.respostaEstaAtual(4, versao), true);
});

test('ignora resposta de consulta anterior', () => {
    const controle = criarControleMapa();

    const antiga = controle.selecionarConsulta(4);
    const atual = controle.selecionarConsulta(2);

    assert.equal(controle.respostaEstaAtual(4, antiga), false);
    assert.equal(controle.respostaEstaAtual(2, atual), true);
});

test('ignora resposta antiga mesmo com o mesmo ID', () => {
    const controle = criarControleMapa();

    const antiga = controle.selecionarConsulta(4);
    const atual = controle.selecionarConsulta(4);

    assert.equal(controle.respostaEstaAtual(4, antiga), false);
    assert.equal(controle.respostaEstaAtual(4, atual), true);
});

test('permite invalidar requisicoes anteriores', () => {
    const controle = criarControleMapa();

    const antiga = controle.selecionarConsulta(4);

    controle.limpar();

    assert.equal(controle.consultaAtual(), null);
    assert.equal(controle.respostaEstaAtual(4, antiga), false);
});

test('mantem versao durante varias respostas da mesma consulta', () => {
    const controle = criarControleMapa();

    const versao = controle.selecionarConsulta(4);

    assert.equal(controle.respostaEstaAtual(4, versao), true);
    assert.equal(controle.respostaEstaAtual(4, versao), true);
});
