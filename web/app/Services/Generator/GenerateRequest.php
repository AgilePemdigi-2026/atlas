<?php

namespace App\Services\Generator;

final readonly class GenerateRequest
{
    /**
     * @param  array<int, array{name: string, scenario_url: string}>  $scenarios
     * @param  array{login_url: string|null, credentials: array<string, string>}|null  $authProfile
     */
    public function __construct(
        public int $testCaseId,
        public string $userStory,
        public string $acceptanceCriteria,
        public ?string $baseUrl,
        public array $scenarios,
        public ?array $authProfile = null,
    ) {}

    /**
     * Service payload. Exactly the defined fields — the service forbids extras.
     * Credentials travel only inside auth_profile, never as free text.
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
            'auth_profile' => $this->authProfile,
            'options' => ['max_retries' => 3],
        ];
    }
}
