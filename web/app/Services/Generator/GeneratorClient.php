<?php

namespace App\Services\Generator;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GeneratorClient
{
    public function __construct(
        private readonly string $url,
        private readonly string $key,
        private readonly int $timeout,
    ) {}

    /**
     * One call to POST /v1/generate.
     * Never retries: the service owns the retry loop
     * and reports it through meta.attempts.
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
                ->post(
                    rtrim($this->url, '/') . '/v1/generate',
                    $request->toPayload()
                );

        } catch (ConnectionException $exception) {

            Log::error('ATLAS generator connection failed', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'endpoint_host' => parse_url($this->url, PHP_URL_HOST),
            ]);

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

        $body = is_array($body)
            ? $body
            : ['detail' => $response->body()];

        $code = $body['error']['code'] ?? null;
        $script = $body['data']['script'] ?? null;

        $ok = $response->status() === 200
            && ($body['status'] ?? null) === 'ok'
            && is_string($script)
            && $script !== '';

        if (! $ok) {
            Log::warning(
                'ATLAS generator returned an unsuccessful response',
                [
                    'http_status' => $response->status(),
                    'error_code' => is_string($code) ? $code : null,
                    'response_status' => $body['status'] ?? null,
                    'has_script' => is_string($script) && $script !== '',
                ]
            );
        }

        return new GeneratorResult(
            ok: $ok,
            payload: $ok ? $body : null,
            error: $ok ? null : $body,
            copy: $ok
                ? ''
                : ErrorCopy::for(
                    is_string($code) ? $code : '',
                    $response->status()
                ),
        );
    }
}