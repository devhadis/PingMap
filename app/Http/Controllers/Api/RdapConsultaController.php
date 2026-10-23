<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EnderecoIpPublico;
use App\Services\RdapConsultaIpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use UnexpectedValueException;
use Throwable;

class RdapConsultaController extends Controller
{
    public function store(
        Request $request,
        RdapConsultaIpService $rdap
    ): JsonResponse {
        $dados = $request->validate([
            'ip' => ['required', 'string', 'max:45'],
        ]);

        try {
            // Validar antes de executar consultas externas.
            $ip = (new EnderecoIpPublico())
                ->validar($dados['ip']);

        } catch (InvalidArgumentException $erro) {
            return response()->json([
                'message' => 'Endereço IP inválido ou não público.',
                'errors' => [
                    'ip' => [$erro->getMessage()],
                ],
            ], 422);
        }

        try {
            $resultado = $rdap->consultar($ip);

            return response()->json([
                'resultado' => $resultado,
            ]);

        } catch (Throwable $erro) {
            report($erro);

            return response()->json([
                'message' => 'Não foi possível concluir a consulta RDAP.',
            ], 503);
        }
    }
}
