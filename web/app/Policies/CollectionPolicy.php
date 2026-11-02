<?php

namespace App\Policies;

use App\Models\Collection;
use App\Models\User;

class CollectionPolicy
{
    public function update(User $user, Collection $collection): bool
    {
        return $collection->user_id === $user->getKey();
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $this->update($user, $collection);
    }
}
