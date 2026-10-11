<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExecutarDiagnostico;
use App\Models\Diagnostico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DiagnosticoController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'destino' => ['required', 'string', 'max:45', 'ip'],
        ]);

        $destino = $dados['destino'];

        if (filter_var(
            $destino,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false) {
            throw ValidationException::withMessages([
                'destino' => 'Informe um IP público permitido.',
            ]);
        }

        $diagnostico = $request->user()
            ->diagnosticos()
            ->create([
                'destino' => $destino,
                'status' => 'pendente',
            ]);

        ExecutarDiagnostico::dispatch($diagnostico->id);

        return response()->json([
            'id' => $diagnostico->id,
            'destino' => $diagnostico->destino,
            'status' => $diagnostico->status,
        ], 202);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $diagnostico = $request->user()
            ->diagnosticos()
            ->findOrFail($id);

        return response()->json([
            'id' => $diagnostico->id,
            'destino' => $diagnostico->destino,
            'status' => $diagnostico->status,
            'perda_percentual' => $diagnostico->perda_percentual,
            'latencia_media_ms' => $diagnostico->latencia_media_ms,
            'latencia_minima_ms' => $diagnostico->latencia_minima_ms,
            'latencia_maxima_ms' => $diagnostico->latencia_maxima_ms,
            'jitter_ms' => $diagnostico->jitter_ms,
            'amostras' => $diagnostico->amostras,
            'created_at' => $diagnostico->created_at,
            'updated_at' => $diagnostico->updated_at,
        ]);
    }
}
