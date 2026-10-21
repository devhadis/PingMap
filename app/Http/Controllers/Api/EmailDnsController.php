<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DnsService;
use App\Services\EmailDnsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class EmailDnsController extends Controller
{
    public function store(
        Request $request,
        DnsService $dns,
        EmailDnsService $email
    ): JsonResponse {
        $dados = $request->validate([
            'dominio' => ['required', 'string', 'max:253'],
            'tipo' => [
                'required',
                'string',
                Rule::in(['SPF', 'DKIM', 'DMARC']),
            ],
            'seletor' => [
                Rule::requiredIf(
                    fn () => $request->input('tipo') === 'DKIM'
                ),
                'nullable',
                'string',
                'max:63',
                'regex:/\A[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\z/',
            ],
        ]);

        try {
            // Valida o domínio sem fazer consulta DNS.
            $entrada = $dns->validarEntrada(
                $dados['dominio'],
                'TXT'
            );

            $dominio = $entrada['dominio'];

            $resultado = match ($dados['tipo']) {
                'SPF' => $email->analisarSpf($dominio),

                'DKIM' => $email->analisarDkim(
                    $dominio,
                    $dados['seletor']
                ),

                'DMARC' => $email->analisarDmarc($dominio),
            };

            return response()->json([
                'resultado' => $resultado,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Não foi possível concluir a análise DNS.',
            ], 503);
        }
    }
}
