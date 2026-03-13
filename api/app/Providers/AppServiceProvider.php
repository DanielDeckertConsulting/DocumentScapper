<?php

namespace App\Providers;

use App\Services\AI\Contracts\ChunkerInterface;
use App\Services\AI\Contracts\ChatAnswererInterface;
use App\Services\AI\Contracts\DocumentClassifierInterface;
use App\Services\AI\Contracts\RetrieverInterface;
use App\Services\AI\Contracts\StructuredExtractorInterface;
use App\Services\AI\Contracts\TextExtractorInterface;
use App\Services\AI\Providers\DbRetriever;
use App\Services\AI\Providers\OpenAiChatAnswerer;
use App\Services\AI\Providers\OpenAiClassifier;
use App\Services\AI\Providers\OpenAiStructuredExtractor;
use App\Services\AI\Providers\PdfTextExtractor;
use App\Services\AI\Providers\SimpleChunker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TextExtractorInterface::class, PdfTextExtractor::class);
        $this->app->bind(DocumentClassifierInterface::class, OpenAiClassifier::class);
        $this->app->bind(StructuredExtractorInterface::class, OpenAiStructuredExtractor::class);
        $this->app->bind(RetrieverInterface::class, DbRetriever::class);
        $this->app->bind(ChatAnswererInterface::class, OpenAiChatAnswerer::class);

        $this->app->bind(ChunkerInterface::class, function () {
            return new SimpleChunker(
                chunkSize: config('ai.chunker.chunk_size', 800),
                overlap: config('ai.chunker.overlap', 100),
            );
        });
    }

    public function boot(): void {}
}
