<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HttpTlsAlvoValidador;
use App\Services\HttpTlsConsultaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class HttpTlsConsultaController extends Controller
{
    public function store(
        Request $request,
        HttpTlsConsultaService $httpTls
    ): JsonResponse {
        // 1. Validar os dados recebidos.
        $dados = $request->validate([
            'dominio' => [
                'required',
                'string',
                'max:253',
            ],
        ]);

        // 2. Validar e normalizar o domínio.
        try {
            $dominio = (new HttpTlsAlvoValidador())
                ->validar($dados['dominio']);

        } catch (InvalidArgumentException $erro) {
            return response()->json([
                'message' => 'Domínio inválido.',
                'errors' => [
                    'dominio' => [
                        'Informe um domínio público válido.',
                    ],
                ],
            ], 422);
        }

        // 3. Executar a consulta HTTP/TLS.
        try {
            $resultado = $httpTls->consultar($dominio);

            return response()->json([
                'resultado' => $resultado,
            ]);

        } catch (InvalidArgumentException $erro) {
            // Não expor detalhes internos do DNS ou dos IPs.
            return response()->json([
                'message' => 'Domínio não permitido.',
                'errors' => [
                    'dominio' => [
                        'Não foi possível validar o destino público.',
                    ],
                ],
            ], 422);

        } catch (Throwable $erro) {
            // Registrar o erro internamente.
            report($erro);

            // Retornar somente uma mensagem genérica.
            return response()->json([
                'message' =>
                    'Não foi possível concluir a consulta HTTP/TLS.',
            ], 503);
        }
    }
}
