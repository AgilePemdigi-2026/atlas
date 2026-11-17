<?php

namespace App\Services\Generator;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class ScenarioUrlClient
{
    public function __construct(
        private readonly string $url,
        private readonly string $key,
        private readonly int $timeout,
    ) {}

    /**
     * Ask the service to split the criteria into scenarios with start URLs.
     * Returns null on any failure so the caller can leave the table unchanged.
     *
     * @return array<int, array{name: string, scenario_url: string|null}>|null
     */
    public function suggest(string $testCaseName, string $criteria, ?string $baseUrl): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Service-Key' => $this->key,
                'Accept' => 'application/json',
            ])
                ->connectTimeout(5)
                ->timeout($this->timeout)
                ->post(rtrim($this->url, '/').'/v1/scenario-urls', [
                    'acceptance_criteria' => $criteria,
                    'base_url' => $baseUrl,
                    'test_case_name' => $testCaseName,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->status() !== 200) {
            return null;
        }

        $body = $response->json();
        if (! is_array($body) || ($body['status'] ?? null) !== 'ok') {
            return null;
        }

        $scenarios = $body['data']['scenarios'] ?? null;
        if (! is_array($scenarios) || $scenarios === []) {
            return null;
        }

        $rows = [];

        foreach ($scenarios as $scenario) {
            if (! is_array($scenario)) {
                return null;
            }

            $name = $scenario['name'] ?? null;
            if (! is_string($name) || trim($name) === '') {
                return null;
            }

            $scenarioUrl = $scenario['scenario_url'] ?? null;
            if ($scenarioUrl !== null && ! is_string($scenarioUrl)) {
                return null;
            }

            $rows[] = [
                'name' => $name,
                'scenario_url' => $scenarioUrl,
            ];
        }

        return $rows;
    }
}
