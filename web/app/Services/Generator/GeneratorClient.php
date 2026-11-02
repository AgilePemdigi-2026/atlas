<?php

namespace App\Services\Generator;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class GeneratorClient
{
    public function __construct(
        private readonly string $url,
        private readonly string $key,
        private readonly int $timeout,
    ) {}

    /**
     * One call to `POST /v1/generate`. Never retries: the service owns the retry
     * loop and reports it through `meta.attempts`.
     */
    public function send(GenerateRequest $request): GeneratorResult
    {
        try {
            $response = Http::withHeaders([
                'X-Service-Key' => $this->key,
                'Accept' => 'application/json',
            ])
                ->connectTimeout(10)
                ->timeout($this->timeout)
                ->post(rtrim($this->url, '/').'/v1/generate', $request->toPayload());
        } catch (ConnectionException) {
            return new GeneratorResult(
                ok: false,
                payload: null,
                error: null,
                copy: ErrorCopy::for('', 0),
            );
        }

        return $this->interpret($response);
    }

    private function interpret(Response $response): GeneratorResult
    {
        $body = $response->json();
        $body = is_array($body) ? $body : ['detail' => $response->body()];

        $code = $body['error']['code'] ?? null;
        $script = $body['data']['script'] ?? null;

        $ok = $response->status() === 200
            && ($body['status'] ?? null) === 'ok'
            && is_string($script)
            && $script !== '';

        return new GeneratorResult(
            ok: $ok,
            payload: $ok ? $body : null,
            error: $ok ? null : $body,
            copy: $ok ? '' : ErrorCopy::for(is_string($code) ? $code : '', $response->status()),
        );
    }
}
