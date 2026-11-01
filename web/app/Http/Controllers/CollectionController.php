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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
        ]);

        $request->user()->collections()->create($validated);

        return redirect()->route('console')->with('status', 'Collection berhasil dibuat.');
    }

    public function update(Request $request, Collection $collection): RedirectResponse
    {
        Gate::authorize('update', $collection);

        $validated = $request->validateWithBag('updateCollection', [
            'name' => ['required', 'string', 'max:256'],
        ]);

        $collection->update($validated);

        return redirect()->route('console')->with('status', 'Nama collection diperbarui.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        Gate::authorize('delete', $collection);

        $collection->delete();

        return redirect()->route('console')->with('status', 'Collection dihapus.');
    }
}
