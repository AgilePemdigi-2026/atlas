<?php

namespace App\Http\Controllers;

use App\Models\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthProfileController extends Controller
{
    // Mencari Test Case yang benar-benar dimiliki user login.
    private function ownedTestCase(Request $request, int $testCaseId): TestCase
    {
        return TestCase::whereHas('collection', function ($query) use ($request) {
            $query->where('user_id', $request->user()->getKey());
        })->findOrFail($testCaseId);
    }

    // F7: Simpan atau perbarui konfigurasi Auth.
    public function upsert(Request $request, int $testCaseId): JsonResponse
    {
        $testCase = $this->ownedTestCase($request, $testCaseId);

        $data = $request->validate([
            'login_url' => ['required', 'url:http,https', 'max:2048'],
            'credentials' => ['required', 'array', 'min:1'],
            'credentials.*' => ['required', 'string', 'max:2048'],
        ]);

        $profile = $testCase->authProfile()->updateOrCreate(
            [],
            [
                'login_url' => $data['login_url'],
                'credentials' => $data['credentials'],
            ]
        );

        return response()->json([
            'message' => 'Auth Profile berhasil disimpan.',
            'data' => [
                'auth_profile_id' => $profile->getKey(),
                'test_case_id' => $testCase->getKey(),
                'login_url' => $profile->login_url,
                'has_credentials' => true,
            ],
        ]);
    }

    // F7: Kembali ke No Auth.
    public function destroy(Request $request, int $testCaseId): JsonResponse
    {
        $testCase = $this->ownedTestCase($request, $testCaseId);

        $testCase->authProfile()->delete();

        return response()->json([
            'message' => 'Auth Profile dihapus. Test Case sekarang menggunakan No Auth.',
        ]);
    }
}