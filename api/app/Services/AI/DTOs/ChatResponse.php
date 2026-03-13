<?php

namespace App\Services\AI\DTOs;

class ChatResponse
{
    public function __construct(
        public readonly string $answer,
        public readonly array $citations = [],
    ) {}
}
