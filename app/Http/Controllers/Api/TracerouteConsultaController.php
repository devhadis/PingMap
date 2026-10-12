<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExecutarConsultaTraceroute;
use App\Services\TracerouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TracerouteConsultaController extends Controller
{
    public function store(
        Request $request,
        TracerouteService $traceroute
    ): JsonResponse {
        $dados = $request->validate([
            'destino' => ['required', 'string', 'max:45'],
        ]);

        try {
            $traceroute->validarDestino($dados['destino']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $consulta = $request->user()
            ->tracerouteConsultas()
            ->create([
                'destino' => $dados['destino'],
                'status' => 'pendente',
            ]);

        ExecutarConsultaTraceroute::dispatch($consulta->id);

        return response()->json([
            'id' => $consulta->id,
            'destino' => $consulta->destino,
            'status' => $consulta->status,
        ], 202);
    }

    public function index(Request $request): JsonResponse
    {
        $consultas = $request->user()
            ->tracerouteConsultas()
            ->latest('id')
            ->limit(20)
            ->get([
                'id',
                'destino',
                'status',
                'created_at',
            ]);

        return response()->json($consultas);
    }

    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $consulta = $request->user()
            ->tracerouteConsultas()
            ->findOrFail($id);

        return response()->json([
            'id' => $consulta->id,
            'destino' => $consulta->destino,
            'status' => $consulta->status,
            'saltos' => $consulta->saltos,
            'erro' => $consulta->erro,
            'created_at' => $consulta->created_at,
            'updated_at' => $consulta->updated_at,
        ]);
    }
}
