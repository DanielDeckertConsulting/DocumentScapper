<?php

namespace App\Services\AI\DTOs;

class ClassificationResult
{
    public function __construct(
        public readonly string $documentType,
        public readonly float $confidence = 0.0,
    ) {}
}
