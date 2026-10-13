import test from 'node:test';
import assert from 'node:assert/strict';

async function calcular(consulta) {
    const modulo = await import('../../public/js/traceroute-indicadores.js');
    return modulo.calcularIndicadoresRota(consulta);
}

test('todos os saltos respondem e o destino é alcançado', async () => {
    const resultado = await calcular({
        destino: '1.1.1.1',
        saltos: [
            { salto: 1, ip: '8.8.8.8', latencia_ms: 10 },
            { salto: 2, ip: '1.1.1.1', latencia_ms: 25.5 }
        ]
    });

    assert.deepEqual(resultado, {
        respondidos: 2,
        semResposta: 0,
        destinoAlcancado: true,
        latenciaDestino: 25.5
    });
});

test('contabiliza saltos intermediários sem resposta', async () => {
    const resultado = await calcular({
        destino: '1.1.1.1',
        saltos: [
            { salto: 1, ip: '8.8.8.8', latencia_ms: 10 },
            { salto: 2, ip: null, latencia_ms: null },
            { salto: 3, ip: '1.1.1.1', latencia_ms: 30 }
        ]
    });

    assert.equal(resultado.respondidos, 2);
    assert.equal(resultado.semResposta, 1);
    assert.equal(resultado.destinoAlcancado, true);
    assert.equal(resultado.latenciaDestino, 30);
});

test('nenhum salto responde', async () => {
    const resultado = await calcular({
        destino: '1.1.1.1',
        saltos: [
            { salto: 1, ip: null, latencia_ms: null },
            { salto: 2, ip: null, latencia_ms: null }
        ]
    });

    assert.deepEqual(resultado, {
        respondidos: 0,
        semResposta: 2,
        destinoAlcancado: false,
        latenciaDestino: null
    });
});

test('latência inválida não conta como resposta', async () => {
    const resultado = await calcular({
        destino: '1.1.1.1',
        saltos: [
            { salto: 1, ip: '8.8.8.8', latencia_ms: -5 },
            { salto: 2, ip: '1.1.1.1', latencia_ms: null }
        ]
    });

    assert.equal(resultado.respondidos, 0);
    assert.equal(resultado.semResposta, 2);
    assert.equal(resultado.destinoAlcancado, false);
    assert.equal(resultado.latenciaDestino, null);
});

test('consulta sem saltos retorna indicadores vazios', async () => {
    const resultado = await calcular({
        destino: '1.1.1.1',
        saltos: []
    });

    assert.deepEqual(resultado, {
        respondidos: 0,
        semResposta: 0,
        destinoAlcancado: false,
        latenciaDestino: null
    });
});
