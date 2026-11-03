<?php

namespace App\Services\Generator;

final readonly class GenerateRequest
{
    /**
     * @param  array<int, array{name: string, scenario_url: string}>  $scenarios
     */
    public function __construct(
        public int $testCaseId,
        public string $userStory,
        public string $acceptanceCriteria,
        public ?string $baseUrl,
        public array $scenarios,
    ) {}

    /**
     * Service payload. Exactly the defined fields — the service forbids extras
     * and credentials never travel through this flow until F7.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'test_case_id' => $this->testCaseId,
            'user_story' => $this->userStory,
            'acceptance_criteria' => $this->acceptanceCriteria,
            'base_url' => $this->baseUrl,
            'scenarios' => $this->scenarios,
            'options' => ['max_retries' => 3],
        ];
    }
}
