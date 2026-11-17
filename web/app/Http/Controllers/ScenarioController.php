<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScenarioController extends Controller
{
    public function __invoke(Request $request, Scenario $scenario): JsonResponse
    {
        Gate::authorize('update', $scenario->testCase);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'scenario_url' => ['nullable', 'url'],
        ], [
            'name.required' => 'Field wajib diisi.',
            'scenario_url.url' => 'Format URL tidak valid.',
        ]);

        $scenario->update($validated);

        return response()->json([
            'message' => 'Skenario disimpan.',
        ]);
    }
}
