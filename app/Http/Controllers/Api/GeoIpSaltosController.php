<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExecutarGeoIpSaltos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GeoIpSaltosController extends Controller
{
    public function traceroute(Request $request, int $id): JsonResponse
    {
        $consulta = $request->user()
            ->tracerouteConsultas()
            ->findOrFail($id);

        return $this->responder(
            'traceroute',
            $consulta,
            (int) $request->user()->id
        );
    }

    public function mtr(Request $request, int $id): JsonResponse
    {
        $consulta = $request->user()
            ->mtrConsultas()
            ->findOrFail($id);

        return $this->responder(
            'mtr',
            $consulta,
            (int) $request->user()->id
        );
    }

    private function responder(
        string $tipo,
        object $consulta,
        int $usuarioId
    ): JsonResponse {
        if ($consulta->status !== 'concluido') {
            return response()->json([
                'message' => 'O diagnóstico ainda não está concluído.',
            ], 409);
        }

        $chave = ExecutarGeoIpSaltos::chave(
            $tipo,
            (int) $consulta->id,
            $usuarioId
        );

        $resultado = Cache::get($chave);

        if (is_array($resultado)) {
            $status = $resultado['status'] ?? 'pendente';

            return response()->json(
                array_diff_key($resultado, ['tentativa' => true]),
                $status === 'concluido' ? 200 :
                    ($status === 'erro' ? 503 : 202)
            );
        }

        // A reserva é protegida pelo lock, mas o Job não é
        // despachado enquanto esse lock estiver ocupado.
        $reserva = Cache::lock($chave . ':lock', 10)->block(
            5,
            function () use ($chave): array {
                $existente = Cache::get($chave);

                if (is_array($existente)) {
                    return [
                        'nova' => false,
                        'dados' => $existente,
                    ];
                }

                $tentativa = (string) Str::uuid();

                $reservado = Cache::add(
                    $chave,
                    [
                        'status' => 'pendente',
                        'tentativa' => $tentativa,
                    ],
                    now()->addMinutes(10)
                );

                if (! $reservado) {
                    return [
                        'nova' => false,
                        'dados' => Cache::get($chave),
                    ];
                }

                return [
                    'nova' => true,
                    'tentativa' => $tentativa,
                ];
            }
        );

        if (! $reserva['nova']) {
            $dados = $reserva['dados'] ?? null;

            if (is_array($dados)) {
                $status = $dados['status'] ?? 'pendente';

                return response()->json(
                    array_diff_key($dados, ['tentativa' => true]),
                    $status === 'concluido' ? 200 :
                        ($status === 'erro' ? 503 : 202)
                );
            }

            return response()->json(['status' => 'pendente'], 202);
        }

        // Neste ponto, o lock da reserva já foi liberado.
        $tentativa = $reserva['tentativa'];

        try {
            ExecutarGeoIpSaltos::dispatch(
                $tipo,
                (int) $consulta->id,
                $usuarioId,
                $tentativa
            );
        } catch (\Throwable $erro) {
            Cache::lock($chave . ':lock', 10)->block(
                5,
                function () use ($chave, $tentativa): void {
                    $atual = Cache::get($chave);

                    if (
                        is_array($atual)
                        && ($atual['status'] ?? null) === 'pendente'
                        && ($atual['tentativa'] ?? null) === $tentativa
                    ) {
                        Cache::forget($chave);
                    }
                }
            );

            throw $erro;
        }

        return response()->json(['status' => 'pendente'], 202);
    }
}
