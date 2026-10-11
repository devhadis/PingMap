<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $diagnosticos = $request->user()
            ->diagnosticos()
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $ultimoComResposta = $diagnosticos->first(
            fn ($diagnostico) =>
                $diagnostico->amostras !== null
                && $diagnostico->amostras > 0
        );

        $serieLatencia = $diagnosticos
            ->filter(
                fn ($diagnostico) =>
                    $diagnostico->latencia_media_ms !== null
            )
            ->reverse()
            ->values()
            ->map(fn ($diagnostico) => [
                'id' => $diagnostico->id,
                'destino' => $diagnostico->destino,
                'latencia' => (float) $diagnostico->latencia_media_ms,
            ])
            ->all();

        return view('dashboard', [
            'diagnosticos' => $diagnosticos,
            'ultimoComResposta' => $ultimoComResposta,
            'serieLatencia' => $serieLatencia,
        ]);
    }
}
