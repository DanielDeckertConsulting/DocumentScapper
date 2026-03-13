<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\ClassificationResult;

interface DocumentClassifierInterface
{
    public function classify(string $rawText): ClassificationResult;
}
