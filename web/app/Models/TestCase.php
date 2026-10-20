<?php

namespace App\Models;

use App\Enums\TestCaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'user_story', 'base_url', 'status'])]
class TestCase extends Model
{
    protected $primaryKey = 'test_case_id';

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => TestCaseStatus::class,
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function authProfile(): HasOne
    {
        return $this->hasOne(AuthProfile::class, 'test_case_id');
    }

    public function scenarios(): HasMany
    {
        return $this->hasMany(Scenario::class, 'test_case_id');
    }

    public function llmOutput(): HasOne
    {
        return $this->hasOne(LlmOutput::class, 'test_case_id');
    }
}
