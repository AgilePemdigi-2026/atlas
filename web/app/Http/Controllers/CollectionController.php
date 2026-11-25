<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CollectionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createCollection', [
            'name' => ['required', 'string', 'max:256'],
        ]);

        $collection = $request->user()->collections()->create($validated);

        return redirect()
            ->route('console', ['collection' => $collection->getKey()])
            ->with('status', 'Collection created successfully.');
    }

    public function update(Request $request, Collection $collection): RedirectResponse
    {
        Gate::authorize('update', $collection);

        $validated = $request->validateWithBag('updateCollection', [
            'name' => ['required', 'string', 'max:256'],
        ]);

        $collection->update($validated);

        return redirect()
            ->route('console', ['collection' => $collection->getKey()])
            ->with('status', 'Collection name updated.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        Gate::authorize('delete', $collection);

        $userId = $collection->user_id;

        $collection->delete();

        $remaining = Collection::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->first();

        return redirect()
            ->route('console', $remaining ? ['collection' => $remaining->getKey()] : [])
            ->with('status', 'Collection deleted.');
    }
}
