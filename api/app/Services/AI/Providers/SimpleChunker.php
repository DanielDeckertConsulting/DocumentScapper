<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ChunkerInterface;
use App\Services\AI\DTOs\ChunkItem;

class SimpleChunker implements ChunkerInterface
{
    private int $chunkSize;
    private int $overlap;

    public function __construct(int $chunkSize = 800, int $overlap = 100)
    {
        $this->chunkSize = $chunkSize;
        $this->overlap = $overlap;
    }

    public function chunk(string $rawText): array
    {
        $words = preg_split('/\s+/', trim($rawText), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return [];
        }

        $chunks = [];
        $index = 0;
        $position = 0;

        while ($position < count($words)) {
            $end = min($position + $this->chunkSize, count($words));
            $chunkWords = array_slice($words, $position, $end - $position);
            $chunkText = implode(' ', $chunkWords);

            $chunks[] = new ChunkItem(
                chunkIndex: $index,
                chunkText: $chunkText,
                tokenCount: count($chunkWords),
            );

            $position += $this->chunkSize - $this->overlap;
            $index++;

            if ($position >= count($words)) {
                break;
            }
        }

        return $chunks;
    }
}
