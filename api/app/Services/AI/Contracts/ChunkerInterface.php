<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\ChunkItem;

interface ChunkerInterface
{
    /** @return ChunkItem[] */
    public function chunk(string $rawText): array;
}
