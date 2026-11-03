<?php

namespace App\Http\Controllers;

use App\Models\TestCase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $testCases = TestCase::query()
            ->whereHas('collection', fn ($query) => $query->where('user_id', $user->user_id))
            ->withCount('scenarios')
            ->latest()
            ->orderByDesc('test_case_id')
            ->paginate(6)
            ->fragment('history');

        return view('profile', [
            'user' => $user,
            'testCases' => $testCases,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
        ]);

        $user->update($data);

        return redirect()->route('profile')->with('status', 'Perubahan berhasil disimpan.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
