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

    public function update(User $user, TestCase $testCase): bool
    {
        return $this->view($user, $testCase);
    }

    public function delete(User $user, TestCase $testCase): bool
    {
        return $this->view($user, $testCase);
    }
}