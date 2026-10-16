<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeoIpSaltosController extends Controller
{
    public function traceroute(Request $request, int $id): JsonResponse
    {
        $consulta = $request->user()
            ->tracerouteConsultas()
            ->findOrFail($id);

        return $this->responder($consulta->status);
    }

    public function mtr(Request $request, int $id): JsonResponse
    {
        $consulta = $request->user()
            ->mtrConsultas()
            ->findOrFail($id);

        return $this->responder($consulta->status);
    }

    private function responder(string $status): JsonResponse
    {
        if ($status !== 'concluido') {
            return response()->json([
                'message' => 'O diagnóstico ainda não está concluído.',
            ], 409);
        }

        // O enriquecimento geográfico será implementado
        // em uma etapa posterior, com controle de chamadas.
        return response()->json([
            'message' => 'Enriquecimento geográfico ainda não implementado.',
        ], 501);
    }
}
