<?php

namespace App\Models;

use App\Enums\GeneratedScriptStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['content', 'status', 'unresolved_step'])]
class GeneratedScript extends Model
{
    const UPDATED_AT = null;

    protected $primaryKey = 'generated_script_id';

    protected function casts(): array
    {
        return [
            'status' => GeneratedScriptStatus::class,
        ];
    }

    public function llmOutput(): BelongsTo
    {
        return $this->belongsTo(LlmOutput::class, 'llm_output_id');
    }
}
