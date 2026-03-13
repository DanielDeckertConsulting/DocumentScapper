<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\DocumentStructuredData;
use App\Services\AI\Contracts\ChunkerInterface;
use App\Services\AI\Contracts\DocumentClassifierInterface;
use App\Services\AI\Contracts\StructuredExtractorInterface;
use App\Services\AI\Contracts\TextExtractorInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 300;

    public function __construct(
        private readonly string $documentId
    ) {}

    public function handle(
        TextExtractorInterface $extractor,
        DocumentClassifierInterface $classifier,
        StructuredExtractorInterface $structuredExtractor,
        ChunkerInterface $chunker,
    ): void {
        $document = Document::findOrFail($this->documentId);
        $document->markAsProcessing();

        // Step 1: Extract text
        $extractionResult = $extractor->extract($document->storage_path);

        if (! $extractionResult->success) {
            $document->markAsFailed($extractionResult->errorCode ?? 'text_extraction_failed');
            AuditLog::record(
                action: 'document.processing_failed',
                userId: $document->user_id,
                entityType: 'document',
                entityId: $document->id,
                meta: ['error_code' => $extractionResult->errorCode]
            );

            return;
        }

        $document->update(['raw_text' => $extractionResult->rawText]);

        // Step 2: Classify document
        $classification = $classifier->classify($extractionResult->rawText);
        $document->update(['document_type' => $classification->documentType]);

        // Step 3: Structured extraction
        $fields = $structuredExtractor->extract($extractionResult->rawText, $classification->documentType);
        $document->update($fields->toDocumentAttributes());
        $document->update(['extraction_version' => 'v1-gpt4o-mini']);

        // Store raw extraction response
        DocumentStructuredData::where('document_id', $document->id)
            ->update(['is_latest' => false]);

        DocumentStructuredData::create([
            'document_id' => $document->id,
            'extractor' => 'openai-gpt4o-mini',
            'is_latest' => true,
        ]);

        // Step 4: Chunking (idempotent)
        DocumentChunk::where('document_id', $document->id)->delete();

        $chunks = $chunker->chunk($extractionResult->rawText);
        foreach ($chunks as $chunk) {
            DocumentChunk::create([
                'document_id' => $document->id,
                'chunk_index' => $chunk->chunkIndex,
                'chunk_text' => $chunk->chunkText,
                'page_reference' => $chunk->pageReference,
                'token_count' => $chunk->tokenCount,
            ]);
        }

        $document->markAsProcessed();
    }

    public function failed(Throwable $exception): void
    {
        $document = Document::find($this->documentId);

        if ($document) {
            $document->markAsFailed('processing_error');
            AuditLog::record(
                action: 'document.processing_failed',
                userId: $document->user_id,
                entityType: 'document',
                entityId: $document->id,
                meta: ['error_code' => 'job_failed']
            );
        }
    }
}
