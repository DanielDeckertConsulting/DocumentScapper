<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\ExtractionFields;

interface StructuredExtractorInterface
{
    public function extract(string $rawText, string $documentType): ExtractionFields;
}
