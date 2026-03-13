<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\ChatRequest;
use App\Services\AI\DTOs\ChatResponse;

interface ChatAnswererInterface
{
    public function answer(ChatRequest $request): ChatResponse;
}
