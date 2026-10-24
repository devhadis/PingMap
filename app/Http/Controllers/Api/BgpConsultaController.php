<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BgpAsnValidador;
use App\Services\BgpConsultaAsnService;
use App\Services\BgpConsultaPrefixosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class BgpConsultaController extends Controller
{
    public function store(
        Request $request,
        BgpAsnValidador $validador,
        BgpConsultaAsnService $consultaAsn,
        BgpConsultaPrefixosService $consultaPrefixos
    ): JsonResponse {
        $dados = $request->validate([
            'asn' => ['required', 'string', 'max:32'],
        ]);

        try {
            $asn = $validador->normalizar($dados['asn']);
        } catch (InvalidArgumentException $erro) {
            return response()->json([
                'message' => 'ASN inválido.',
                'errors' => [
                    'asn' => [$erro->getMessage()],
                ],
            ], 422);
        }

        try {
            $informacoes = $consultaAsn->consultar($asn);
            $prefixos = $consultaPrefixos->consultar($asn);

            return response()->json([
                'resultado' => [
                    ...$informacoes,
                    'prefixos' => $prefixos,
                ],
            ]);
        } catch (Throwable $erro) {
            report($erro);

            return response()->json([
                'message' => 'Não foi possível concluir a consulta BGP.',
            ], 503);
        }
    }
}
