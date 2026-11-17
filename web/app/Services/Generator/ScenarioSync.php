<?php

namespace App\Services\Generator;

use App\Models\TestCase;
use Illuminate\Support\Facades\DB;

final class ScenarioSync
{
    public function __construct(private readonly ScenarioUrlClient $client) {}

    public static function fingerprint(string $criteria, ?string $baseUrl): string
    {
        return hash('sha256', trim($criteria)."\n".trim((string) $baseUrl));
    }

    /**
     * Replace the test case's scenarios with the service's inference.
     * Returns false when the service is unavailable or returns nothing usable.
     */
    public function apply(TestCase $testCase, string $criteria, ?string $baseUrl): bool
    {
        $rows = $this->client->suggest($testCase->name, $criteria, $baseUrl);

        if ($rows === null) {
            return false;
        }

        DB::transaction(function () use ($testCase, $rows, $criteria, $baseUrl): void {
            $testCase->scenarios()->delete();

            foreach ($rows as $row) {
                $testCase->scenarios()->create([
                    'name' => $row['name'],
                    'scenario_url' => $row['scenario_url'],
                ]);
            }

            $testCase->scenarios_synced_hash = self::fingerprint($criteria, $baseUrl);
            $testCase->save();
        });

        return true;
    }
}
