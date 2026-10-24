<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BgpAlvoValidador;
use App\Services\BgpConsultaRedeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class BgpConsultaRedeController extends Controller
{
    public function store(
        Request $request,
        BgpAlvoValidador $validador,
        BgpConsultaRedeService $consultaRede
    ): JsonResponse {
        $dados = $request->validate([
            'alvo' => ['required', 'string', 'max:253'],
        ]);

        try {
            // Validar antes de qualquer consulta externa.
            $alvo = $validador->normalizar($dados['alvo']);
        } catch (InvalidArgumentException $erro) {
            return response()->json([
                'message' => 'Alvo BGP inválido.',
                'errors' => [
                    'alvo' => [$erro->getMessage()],
                ],
            ], 422);
        }

        try {
            // Reutilizar o serviço já testado.
            $resultado = $consultaRede->consultar(
                $alvo['valor']
            );

            return response()->json([
                'resultado' => $resultado,
            ]);
        } catch (Throwable $erro) {
            report($erro);

            return response()->json([
                'message' => 'Não foi possível concluir a consulta BGP por rede.',
            ], 503);
        }
    }
}
