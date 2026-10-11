<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExecutarConsultaTcp;
use App\Services\TcpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TcpConsultaController extends Controller
{
    public function store(
        Request $request,
        TcpService $tcp
    ): JsonResponse {
        $dados = $request->validate([
            'destino' => ['required', 'string', 'max:45'],
            'porta' => ['required', 'integer', 'between:1,65535'],
        ]);

        try {
            $entrada = $tcp->validarEntrada(
                $dados['destino'],
                (int) $dados['porta']
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $consulta = $request->user()
            ->tcpConsultas()
            ->create([
                'destino' => $entrada['destino'],
                'porta' => $entrada['porta'],
                'status' => 'pendente',
            ]);

        ExecutarConsultaTcp::dispatch($consulta->id);

        return response()->json([
            'id' => $consulta->id,
            'destino' => $consulta->destino,
            'porta' => $consulta->porta,
            'status' => $consulta->status,
        ], 202);
    }

    public function index(Request $request): JsonResponse
    {
        $consultas = $request->user()
            ->tcpConsultas()
            ->latest('id')
            ->limit(20)
            ->get([
                'id',
                'destino',
                'porta',
                'status',
                'tempo_ms',
                'created_at',
            ]);

        return response()->json($consultas);
    }

    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $consulta = $request->user()
            ->tcpConsultas()
            ->findOrFail($id);

        return response()->json([
            'id' => $consulta->id,
            'destino' => $consulta->destino,
            'porta' => $consulta->porta,
            'status' => $consulta->status,
            'tempo_ms' => $consulta->tempo_ms,
            'erro' => $consulta->erro,
            'created_at' => $consulta->created_at,
            'updated_at' => $consulta->updated_at,
        ]);
    }
}
