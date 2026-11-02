<?php

namespace App\Http\Controllers;

use App\Models\TestCase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsoleController extends Controller
{
    public const SCRIPT_EXTENSION = 'py';

    public function __invoke(Request $request): View
    {
        $collections = $request->user()
            ->collections()
            ->with(['testCases' => fn ($query) => $query
                ->select(['test_case_id', 'collection_id', 'name', 'status'])
                ->latest(),
            ])
            ->orderBy('name')
            ->get();

        $selected = $collections
            ->flatMap(fn ($collection) => $collection->testCases)
            ->firstWhere('test_case_id', $request->integer('test_case'));

        $selected?->load('generatedScript')->loadCount('scenarios');

        return view('console', ['collections' => $collections, 'selected' => $selected]);
    }

    public function download(TestCase $testCase): StreamedResponse
    {
        Gate::authorize('view', $testCase);

        $script = $testCase->generatedScript;

        abort_if($script === null, 404);

        return response()->streamDownload(function () use ($script) {
            echo $script->content;
        }, self::scriptFilename($testCase), ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public static function scriptFilename(TestCase $testCase): string
    {
        return 'test_'.(Str::slug($testCase->name, '_') ?: 'test_case').'.'.self::SCRIPT_EXTENSION;
    }
}
