<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TestCaseController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'collection_id' => [
                'required',
                'integer',
                Rule::exists('collections', 'collection_id')
                    ->where('user_id', $user->getKey()),
            ],
            'name' => ['required', 'string', 'max:256'],
            'user_story' => ['required', 'string'],
            'acceptance_criteria' => ['required', 'string'],
            'base_url' => ['nullable', 'url', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi input gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $collection = $user->collections()
            ->findOrFail($data['collection_id']);

        $testCase = $collection->testCases()->create([
            'name' => $data['name'],
            'user_story' => $data['user_story'],
            'acceptance_criteria' => $data['acceptance_criteria'],
            'base_url' => $data['base_url'] ?? null,
        ]);

        return response()->json([
            'message' => 'Test Case berhasil disimpan.',
            'data' => $testCase,
        ], 201);
    }
}