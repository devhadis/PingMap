<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExecutarConsultaDns;
use App\Services\DnsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DnsConsultaController extends Controller
{
    public function store(Request $request, DnsService $dns): JsonResponse
    {
        $dados = $request->validate([
            'dominio' => ['required', 'string', 'max:253'],
            'tipo' => ['required', 'string', 'max:8'],
        ]);

        try {
            $entrada = $dns->validarEntrada(
                $dados['dominio'],
                $dados['tipo']
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $consulta = $request->user()->dnsConsultas()->create([
            'dominio' => $entrada['dominio'],
            'tipo' => $entrada['tipo'],
            'status' => 'pendente',
        ]);

        ExecutarConsultaDns::dispatch($consulta->id);

        return response()->json([
            'id' => $consulta->id,
            'dominio' => $consulta->dominio,
            'tipo' => $consulta->tipo,
            'status' => $consulta->status,
        ], 202);
    }

    public function index(Request $request): JsonResponse
    {
        $consultas = $request->user()
            ->dnsConsultas()
            ->latest('id')
            ->limit(20)
            ->get([
                'id',
                'dominio',
                'tipo',
                'status',
                'quantidade',
                'created_at',
            ]);

        return response()->json($consultas);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $consulta = $request->user()
            ->dnsConsultas()
            ->findOrFail($id);

        return response()->json([
            'id' => $consulta->id,
            'dominio' => $consulta->dominio,
            'tipo' => $consulta->tipo,
            'status' => $consulta->status,
            'quantidade' => $consulta->quantidade,
            'registros' => $consulta->registros,
            'erro' => $consulta->erro,
            'created_at' => $consulta->created_at,
            'updated_at' => $consulta->updated_at,
        ]);
    }
}
