<?php

namespace App\Services\AI\DTOs;

class ChatRequest
{
    public function __construct(
        public readonly string $question,
        public readonly array $documentContexts,
        public readonly array $chunks,
        public readonly array $chatHistory = [],
    ) {}
}
