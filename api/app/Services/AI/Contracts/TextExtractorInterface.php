<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\ExtractionResult;

interface TextExtractorInterface
{
    public function extract(string $storagePath): ExtractionResult;
}
