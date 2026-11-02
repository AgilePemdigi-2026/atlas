<?php

namespace App\Http\Controllers;

use App\Models\TestCase;
use App\Services\Generator\ScenarioParser;
use App\Services\RootCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            'base_url' => $validated['base_url'] ?? null,
        ]);

        foreach (ScenarioParser::names($validated['acceptance_criteria'], $validated['name']) as $name) {
            $testCase->scenarios()->create(['name' => $name]);
        }

        return redirect()->route('test-cases.scenario-urls', $testCase);
    }

    public function scenarioUrls(Request $request, TestCase $testCase): View
    {
        return view('test-cases.scenario-urls', ['testCase' => $testCase]);
    }
}
