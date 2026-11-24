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
use App\Services\Generator\ScenarioSync;
use App\Services\Generator\ScenarioUrlClient;
use App\Services\RootCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TestCaseController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $bag = $request->input('source') === 'empty' ? 'createFirstTestCase' : 'createTestCase';

        $validated = $request->validateWithBag($bag, [
            'name' => ['required', 'string', 'max:256'],
            'collection_id' => ['nullable', 'integer'],
        ], [
            'name.required' => 'Field wajib diisi.',
        ]);

        $user = $request->user();

        $collection = $validated['collection_id'] ?? null
            ? $user->collections()->findOrFail($validated['collection_id'])
            : RootCollection::for($user);

        $testCase = $collection->testCases()->create([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('console', [
                'collection' => $collection->getKey(),
                'test_case' => $testCase->getKey(),
                'focus' => 1,
            ])
            ->with('status', 'Test case berhasil dibuat.');
    }

    public function update(Request $request, TestCase $testCase): RedirectResponse
    {
        Gate::authorize('update', $testCase);

        if ($request->boolean('rename')) {
            $validated = $request->validateWithBag('renameTestCase', [
                'name' => ['required', 'string', 'max:256'],
            ], [
                'name.required' => 'Field wajib diisi.',
            ]);

            $testCase->update($validated);

            return redirect()
                ->route('console', [
                    'collection' => $testCase->collection_id,
                    'test_case' => $testCase->getKey(),
                ])
                ->with('status', 'Nama test case diperbarui.');
        }

        if (! $request->has('user_story')) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:256'],
            ], [
                'name.required' => 'Field wajib diisi.',
            ]);

            $testCase->update($validated);

            return redirect()
                ->route('console', ['test_case' => $testCase->getKey()])
                ->with('status', 'Nama test case diperbarui.');
        }

        $validated = $request->validateWithBag('updateTestCase', [
            'name' => ['required', 'string', 'max:256'],
            'user_story' => ['required', 'string'],
            'acceptance_criteria' => ['required', 'string'],
            'base_url' => ['nullable', 'url'],
        ], [
            'name.required' => 'Field wajib diisi.',
            'user_story.required' => 'Field wajib diisi.',
            'acceptance_criteria.required' => 'Field wajib diisi.',
        ]);

        $notice = $this->applyStory($testCase, $validated);

        $redirect = redirect()
            ->route('console', [
                'test_case' => $testCase->getKey(),
                'collection' => $testCase->collection_id,
                'tab' => 'scenario',
            ])
            ->with('status', 'Test case diperbarui.');

        return $notice === null ? $redirect : $redirect->withErrors(['sync' => $notice]);
    }

    public function destroy(TestCase $testCase): RedirectResponse
    {
        Gate::authorize('delete', $testCase);

        $collectionId = $testCase->collection_id;

        $testCase->delete();

        return redirect()
            ->route('console', ['collection' => $collectionId])
            ->with('status', 'Test case dihapus.');
    }

    private function applyStory(TestCase $testCase, array $validated): ?string
    {
        $criteria = $validated['acceptance_criteria'];
        $baseUrl = $validated['base_url'] ?? null;

        DB::transaction(function () use ($testCase, $validated): void {
            $testCase->update([
                'name' => $validated['name'],
                'user_story' => $validated['user_story'],
                'acceptance_criteria' => $validated['acceptance_criteria'],
                'base_url' => $validated['base_url'] ?? null,
            ]);
        });

        if ($testCase->scenarios_synced_hash === ScenarioSync::fingerprint($criteria, $baseUrl)) {
            return null;
        }

        $sync = new ScenarioSync(new ScenarioUrlClient(
            url: (string) config('services.generator.url'),
            key: (string) config('services.generator.key'),
            timeout: (int) config('services.generator.scenario_timeout'),
        ));

        if ($sync->apply($testCase, $criteria, $baseUrl)) {
            return null;
        }

        if ($testCase->scenarios()->doesntExist()) {
            foreach (ScenarioParser::names($criteria, $validated['name']) as $name) {
                $testCase->scenarios()->create(['name' => $name]);
            }
        }

        return 'Sinkronisasi gagal, daftar skenario tidak diubah.';
    }

    public function scenarioUrls(TestCase $testCase): RedirectResponse
    {
        Gate::authorize('update', $testCase->collection);

        return redirect()->route('console', [
            'test_case' => $testCase->getKey(),
            'collection' => $testCase->collection_id,
            'tab' => 'scenario',
        ]);
    }

    public function generate(Request $request, TestCase $testCase): RedirectResponse
    {
        Gate::authorize('update', $testCase->collection);

        $validated = $request->validate([
            'scenarios' => ['required', 'array', 'min:1'],
            'scenarios.*.scenario_id' => ['required', 'integer'],
            'scenarios.*.name' => ['sometimes', 'required', 'string', 'max:256'],
            'scenarios.*.scenario_url' => ['required', 'url'],
        ], [
            'scenarios.*.name.required' => 'Field wajib diisi.',
            'scenarios.*.scenario_url.required' => 'Field wajib diisi.',
            'scenarios.*.scenario_url.url' => 'Format URL tidak valid.',
        ]);

        foreach ($validated['scenarios'] as $row) {
            $updates = ['scenario_url' => $row['scenario_url']];

            if (array_key_exists('name', $row)) {
                $updates['name'] = $row['name'];
            }

            $testCase->scenarios()
                ->whereKey($row['scenario_id'])
                ->update($updates);
        }

        $result = $this->callService($testCase, $validated['scenarios']);

        if (! $result->ok) {
            $this->recordFailure($testCase, $result);

            return redirect()
                ->route('console', [
                    'test_case' => $testCase->getKey(),
                    'collection' => $testCase->collection_id,
                    'tab' => 'scenario',
                ])
                ->withInput()
                ->withErrors(['generate' => $result->copy]);
        }

        $this->recordSuccess($testCase, $result->payload);

        return redirect()
            ->route('console', [
                'test_case' => $testCase->getKey(),
                'collection' => $testCase->collection_id,
                'tab' => 'script',
            ])
            ->with('status', 'Test case berhasil dibuat.');
    }

    public function show(TestCase $testCase): RedirectResponse
    {
        Gate::authorize('update', $testCase->collection);

        return redirect()->route('console', [
            'test_case' => $testCase->getKey(),
            'collection' => $testCase->collection_id,
            'tab' => $testCase->generatedScript ? 'script' : 'scenario',
        ]);
    }

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
            userStory: (string) $testCase->user_story,
            acceptanceCriteria: $testCase->acceptance_criteria ?? $testCase->user_story,
            baseUrl: $testCase->base_url,
            scenarios: $scenarios,
            authProfile: $testCase->authProfile === null ? null : [
                'login_url' => $testCase->authProfile->login_url,
                'credentials' => $testCase->authProfile->credentials ?? [],
                'submit_selector' => $testCase->authProfile->submit_selector,
                'success_indicator' => $testCase->authProfile->success_indicator,
            ],
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
