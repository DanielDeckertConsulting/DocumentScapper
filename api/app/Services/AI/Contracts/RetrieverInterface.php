<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\ChunkItem;

interface RetrieverInterface
{
    /** @return ChunkItem[] */
    public function retrieve(
        string $query,
        string $userId,
        ?string $documentId = null,
        int $limit = 5
    ): array;
}
