<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['credentials', 'login_url', 'submit_selector', 'success_indicator'])]
#[Hidden(['credentials'])]
class AuthProfile extends Model
{
    protected $primaryKey = 'auth_profile_id';

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'submit_selector' => 'string',
            'success_indicator' => 'string',
        ];
    }

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class, 'test_case_id');
    }
}
