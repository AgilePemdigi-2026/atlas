<?php

namespace App\Policies;

use App\Models\TestCase;
use App\Models\User;

class TestCasePolicy
{
    public function view(User $user, TestCase $testCase): bool
    {
        return $testCase->collection->user_id === $user->getKey();
    }
}
