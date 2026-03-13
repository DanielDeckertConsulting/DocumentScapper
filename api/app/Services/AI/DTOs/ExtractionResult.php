<?php

namespace App\Services\AI\DTOs;

class ExtractionResult
{
    public function __construct(
        public readonly string $rawText,
        public readonly bool $success,
        public readonly ?int $pageCount = null,
        public readonly ?string $errorCode = null,
    ) {}

    public static function success(string $rawText, ?int $pageCount = null): self
    {
        return new self(rawText: $rawText, success: true, pageCount: $pageCount);
    }

    public static function failure(string $errorCode): self
    {
        return new self(rawText: '', success: false, errorCode: $errorCode);
    }
}
