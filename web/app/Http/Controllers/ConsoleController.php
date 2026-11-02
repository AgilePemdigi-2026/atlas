<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ConsoleController extends Controller
{
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

        return view('console', [
            'collections' => $collections,
            'activeCollectionId' => $request->integer('collection') ?: null,
        ]);
    }
}
