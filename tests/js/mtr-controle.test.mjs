import test from 'node:test';
import assert from 'node:assert/strict';

import {
    criarControleMtr
} from '../../public/js/mtr-controle.js';

test('inicia sem consulta selecionada', () => {
    const controle = criarControleMtr();

    assert.equal(controle.consultaAtual(), null);
});

test('seleciona uma consulta', () => {
    const controle = criarControleMtr();

    controle.selecionarConsulta(2);

    assert.equal(controle.consultaAtual(), 2);
    assert.equal(controle.consultaEstaAtiva(2), true);
});

test('consulta antiga deixa de estar ativa', () => {
    const controle = criarControleMtr();

    controle.selecionarConsulta(2);
    controle.selecionarConsulta(3);

    assert.equal(controle.consultaEstaAtiva(2), false);
    assert.equal(controle.consultaEstaAtiva(3), true);
});

test('historico antigo nao substitui historico recente', () => {
    const controle = criarControleMtr();

    const requisicaoAntiga = controle.iniciarHistorico();
    const requisicaoRecente = controle.iniciarHistorico();

    assert.equal(
        controle.historicoEstaAtual(requisicaoAntiga),
        false
    );

    assert.equal(
        controle.historicoEstaAtual(requisicaoRecente),
        true
    );
});

test('consulta selecionada nao e apagada pelo historico', () => {
    const controle = criarControleMtr();

    controle.selecionarConsulta(5);
    controle.iniciarHistorico();

    assert.equal(controle.consultaAtual(), 5);
});
