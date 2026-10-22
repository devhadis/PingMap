<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DnsService;
use App\Services\DnssecValidadorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DnssecController extends Controller
{
    public function store(
        Request $request,
        DnsService $dns,
        DnssecValidadorService $dnssec
    ): JsonResponse {
        $dados = $request->validate([
            'dominio' => [
                'required',
                'string',
                'max:253',
            ],
        ]);

        try {
            // Validar antes de executar qualquer consulta.
            $entrada = $dns->validarEntrada(
                $dados['dominio'],
                'A'
            );

            $dominio = $entrada['dominio'];

            $estado = $dnssec->executar($dominio);

            return response()->json([
                'resultado' => [
                    'dominio' => $dominio,
                    'tipo' => 'A',
                    'estado' => $estado,
                ],
            ]);
        } catch (InvalidArgumentException $erro) {
            return response()->json([
                'message' => $erro->getMessage(),
            ], 422);
        } catch (\Throwable $erro) {
            report($erro);

            return response()->json([
                'message' => 'Não foi possível concluir a análise DNSSEC.',
            ], 503);
        }
    }
}
