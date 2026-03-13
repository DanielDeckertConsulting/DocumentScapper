<?php

namespace App\Services\AI\DTOs;

class ChunkItem
{
    public function __construct(
        public readonly int $chunkIndex,
        public readonly string $chunkText,
        public readonly ?int $pageReference = null,
        public readonly int $tokenCount = 0,
        public readonly ?string $documentId = null,
        public readonly ?string $documentTitle = null,
    ) {}
}
