<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExecutarConsultaMtr;
use App\Services\MtrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class MtrConsultaController extends Controller
{
    public function store(
        Request $request,
        MtrService $mtr
    ): JsonResponse {
        $dados = $request->validate([
            'destino' => ['required', 'string', 'max:45'],
        ]);

        try {
            $mtr->validarDestino($dados['destino']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Destino IPv4 público inválido.',
            ], 422);
        }

        $consulta = $request->user()
            ->mtrConsultas()
            ->create([
                'destino' => $dados['destino'],
                'status' => 'pendente',
            ]);

        ExecutarConsultaMtr::dispatch($consulta->id);

        return response()->json([
            'id' => $consulta->id,
            'destino' => $consulta->destino,
            'status' => $consulta->status,
        ], 202);
    }

    public function index(Request $request): JsonResponse
    {
        $consultas = $request->user()
            ->mtrConsultas()
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
            ->mtrConsultas()
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
