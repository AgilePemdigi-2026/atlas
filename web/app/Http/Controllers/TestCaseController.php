<?php

namespace App\Http\Controllers;

use App\Enums\GeneratedScriptStatus;
use App\Enums\LlmValidationStatus;
use App\Enums\TestCaseStatus;
use App\Models\Scenario;
use App\Models\TestCase;
use App\Services\Generator\GenerateRequest;
use App\Services\Generator\GeneratorClient;
use App\Services\Generator\GeneratorResult;
use App\Services\Generator\ScenarioParser;
use App\Services\RootCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TestCaseController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'user_story' => ['required', 'string'],
            'acceptance_criteria' => ['required', 'string'],
            'base_url' => ['nullable', 'url'],
            'collection_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();

        $collection = $validated['collection_id'] ?? null
            ? $user->collections()->findOrFail($validated['collection_id'])
            : RootCollection::for($user);

        $testCase = $collection->testCases()->create([
            'name' => $validated['name'],
            'user_story' => $validated['user_story'],
            'acceptance_criteria' => $validated['acceptance_criteria'],
            'base_url' => $validated['base_url'] ?? null,
        ]);

        foreach (ScenarioParser::names($validated['acceptance_criteria'], $validated['name']) as $name) {
            $testCase->scenarios()->create(['name' => $name]);
        }

        return redirect()->route('test-cases.scenario-urls', $testCase);
    }

    public function scenarioUrls(TestCase $testCase): View
    {
        Gate::authorize('update', $testCase->collection);

        $testCase->load('scenarios');

        return view('test-cases.scenario-urls', ['testCase' => $testCase]);
    }

    public function generate(Request $request, TestCase $testCase): RedirectResponse
    {
        Gate::authorize('update', $testCase->collection);

        $validated = $request->validate([
            'scenarios' => ['required', 'array', 'min:1'],
            'scenarios.*.scenario_id' => ['required', 'integer'],
            'scenarios.*.scenario_url' => ['required', 'url'],
        ], [
            'scenarios.*.scenario_url.required' => 'Field wajib diisi.',
            'scenarios.*.scenario_url.url' => 'Format URL tidak valid.',
        ]);

        foreach ($validated['scenarios'] as $row) {
            $testCase->scenarios()
                ->whereKey($row['scenario_id'])
                ->update(['scenario_url' => $row['scenario_url']]);
        }

        $result = $this->callService($testCase, $validated['scenarios']);

        if (! $result->ok) {
            $this->recordFailure($testCase, $result);

            return redirect()
                ->route('test-cases.scenario-urls', $testCase)
                ->withInput()
                ->withErrors(['generate' => $result->copy]);
        }

        $this->recordSuccess($testCase, $result->payload);

        return redirect()
            ->route('test-cases.show', $testCase)
            ->with('status', 'Test case berhasil dibuat.');
    }

    public function show(TestCase $testCase): View
    {
        Gate::authorize('update', $testCase->collection);

        $testCase->load('scenarios', 'llmOutput.generatedScript');

        $meta = $testCase->llmOutput?->output;

        return view('test-cases.show', [
            'testCase' => $testCase,
            'script' => $testCase->llmOutput?->generatedScript?->content,
            'meta' => is_array($meta) ? $meta : null,
            'statusCopy' => match ($testCase->status) {
                TestCaseStatus::Pending => 'Menunggu generate.',
                TestCaseStatus::Completed => 'Generate selesai.',
                TestCaseStatus::Failed => 'Generate gagal.',
            },
        ]);
    }

    /**
     * Send the confirmed scenario URLs to the generator service.
     *
     * @param  array<int, array{scenario_id: int|string, scenario_url: string}>  $rows
     */
    private function callService(TestCase $testCase, array $rows): GeneratorResult
    {
        $submittedUrls = collect($rows)->keyBy('scenario_id');

        $scenarios = $testCase->scenarios()->get()->map(fn (Scenario $scenario): array => [
            'name' => $scenario->name,
            'scenario_url' => (string) ($submittedUrls->get($scenario->getKey())['scenario_url'] ?? $scenario->scenario_url),
        ])->values()->all();

        return (new GeneratorClient(
            url: (string) config('services.generator.url'),
            key: (string) config('services.generator.key'),
            timeout: (int) config('services.generator.timeout'),
        ))->send(new GenerateRequest(
            testCaseId: $testCase->getKey(),
            userStory: $testCase->user_story,
            acceptanceCriteria: $testCase->acceptance_criteria ?? $testCase->user_story,
            baseUrl: $testCase->base_url,
            scenarios: $scenarios,
        ));
    }

    private function recordFailure(TestCase $testCase, GeneratorResult $result): void
    {
        DB::transaction(function () use ($testCase, $result): void {
            $testCase->update(['status' => TestCaseStatus::Failed]);

            $output = $testCase->llmOutput()->updateOrCreate([], [
                'output' => $result->error ?? [],
                'validation_status' => LlmValidationStatus::Invalid,
                'retry_count' => (int) ($result->error['meta']['attempts'] ?? 0),
            ]);

            // A failed re-generate must not leave the previous script looking current.
            $output->generatedScript()->update(['status' => GeneratedScriptStatus::Failed]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordSuccess(TestCase $testCase, array $payload): void
    {
        $script = $payload['data']['script'];
        unset($payload['data']['script']);

        DB::transaction(function () use ($testCase, $payload, $script): void {
            $testCase->update(['status' => TestCaseStatus::Completed]);

            $output = $testCase->llmOutput()->updateOrCreate([], [
                'output' => $payload,
                'validation_status' => LlmValidationStatus::Valid,
                'retry_count' => (int) ($payload['meta']['attempts'] ?? 0),
            ]);

            $output->generatedScript()->updateOrCreate([], [
                'content' => $script,
                'status' => GeneratedScriptStatus::Completed,
            ]);
        });
    }
}
