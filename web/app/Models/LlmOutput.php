<?php

namespace App\Models;

use App\Enums\LlmValidationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['output', 'validation_status', 'retry_count'])]
class LlmOutput extends Model
{
    protected $primaryKey = 'llm_output_id';

    protected $attributes = [
        'validation_status' => 'pending',
        'retry_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'validation_status' => LlmValidationStatus::class,
            'retry_count' => 'integer',
        ];
    }

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class, 'test_case_id');
    }

    public function generatedScript(): HasOne
    {
        return $this->hasOne(GeneratedScript::class, 'llm_output_id');
    }
}
