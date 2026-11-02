<?php

namespace App\Services\Generator;

final readonly class GeneratorResult
{
    /**
     * @param  array<string, mixed>|null  $payload  decoded ok envelope
     * @param  array<string, mixed>|null  $error  decoded error payload (envelope or HTTPException body)
     */
    public function __construct(
        public bool $ok,
        public ?array $payload,
        public ?array $error,
        public string $copy,
    ) {}
}
