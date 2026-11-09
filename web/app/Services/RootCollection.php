<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\User;

final class RootCollection
{
    /**
     * The per-user root collection every generate defaults into.
     */
    public static function for(User $user): Collection
    {
        return $user->collections()->firstOrCreate(['name' => 'Default']);
    }
}
