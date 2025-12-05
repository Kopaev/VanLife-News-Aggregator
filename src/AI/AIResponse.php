<?php

declare(strict_types=1);

namespace App\AI;

class AIResponse
{
    public function __construct(
        public readonly string $content,
        public readonly array $usage = [],
        public readonly ?string $model = null,
        public readonly ?string $finishReason = null,
        public readonly array $raw = [],
    ) {
    }
}
