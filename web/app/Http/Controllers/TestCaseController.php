<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Models\TestCase;
use App\Services\Generator\GenerateRequest;
use App\Services\Generator\GeneratorClient;
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

    public function scenarioUrls(Request $request, TestCase $testCase): View
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

        $submittedUrls = collect($validated['scenarios'])->keyBy('scenario_id');

        $scenarios = $testCase->scenarios()->get()->map(fn (Scenario $scenario): array => [
            'name' => $scenario->name,
            'scenario_url' => (string) ($submittedUrls->get($scenario->getKey())['scenario_url'] ?? $scenario->scenario_url),
        ])->values()->all();

        $result = (new GeneratorClient(
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

        if (! $result->ok) {
            DB::transaction(function () use ($testCase, $result): void {
                $testCase->update(['status' => 'failed']);
                $testCase->llmOutput()->updateOrCreate([], [
                    'output' => $result->error ?? [],
                    'validation_status' => 'invalid',
                    'retry_count' => (int) ($result->error['meta']['attempts'] ?? 0),
                ]);
            });

            return redirect()
                ->route('test-cases.scenario-urls', $testCase)
                ->withInput()
                ->withErrors(['generate' => $result->copy]);
        }

        $payload = $result->payload;
        $script = $payload['data']['script'];
        unset($payload['data']['script']);

        DB::transaction(function () use ($testCase, $payload, $script): void {
            $testCase->update(['status' => 'completed']);

            $output = $testCase->llmOutput()->updateOrCreate([], [
                'output' => $payload,
                'validation_status' => 'valid',
                'retry_count' => (int) ($payload['meta']['attempts'] ?? 0),
            ]);

            $output->generatedScript()->updateOrCreate([], [
                'content' => $script,
                'status' => 'completed',
            ]);
        });

        return redirect()
            ->route('test-cases.show', $testCase)
            ->with('status', 'Test case berhasil dibuat.');
    }

    public function show(Request $request, TestCase $testCase): View
    {
        Gate::authorize('update', $testCase->collection);

        $testCase->load('scenarios', 'llmOutput.generatedScript');

        return view('test-cases.show', ['testCase' => $testCase]);
    }
}
