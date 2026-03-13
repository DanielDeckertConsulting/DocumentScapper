<?php

namespace Tests\Feature\Document;

use App\Jobs\ProcessDocumentJob;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\User;
use App\Services\AI\Contracts\ChunkerInterface;
use App\Services\AI\Contracts\DocumentClassifierInterface;
use App\Services\AI\Contracts\StructuredExtractorInterface;
use App\Services\AI\Contracts\TextExtractorInterface;
use App\Services\AI\DTOs\ChunkItem;
use App\Services\AI\DTOs\ClassificationResult;
use App\Services\AI\DTOs\ExtractionFields;
use App\Services\AI\DTOs\ExtractionResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProcessDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_processing_marks_document_as_processed(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $document = Document::factory()->for($user)->uploaded()->create([
            'storage_path' => 'documents/test/test.pdf',
        ]);

        Storage::disk('local')->put('documents/test/test.pdf', '%PDF fake content');

        $this->bindMockServices(
            extractionResult: ExtractionResult::success('Haftpflichtversicherung Mustervertrag', 2),
            classification: new ClassificationResult('haftpflichtversicherung', 0.95),
            fields: new ExtractionFields(
                title: 'Haftpflichtversicherung 2024',
                summary: 'Zusammenfassung des Vertrags',
                counterpartyName: 'Mustermann Versicherung GmbH',
                paymentAmount: 120.00,
                paymentCurrency: 'EUR',
                paymentInterval: 'jährlich',
            ),
            chunks: [
                new ChunkItem(chunkIndex: 0, chunkText: 'Haftpflicht', pageReference: 1, tokenCount: 10),
                new ChunkItem(chunkIndex: 1, chunkText: 'Vertrag', pageReference: 1, tokenCount: 8),
            ]
        );

        ProcessDocumentJob::dispatchSync($document->id);

        $document->refresh();
        $this->assertEquals('processed', $document->status);
        $this->assertNotNull($document->processed_at);
        $this->assertEquals('haftpflichtversicherung', $document->document_type);
        $this->assertEquals('Haftpflichtversicherung 2024', $document->title);
        $this->assertEquals('Mustermann Versicherung GmbH', $document->counterparty_name);
        $this->assertNotNull($document->extraction_version);
        $this->assertNull($document->processing_error);

        $this->assertDatabaseHas('document_structured_data', [
            'document_id' => $document->id,
            'is_latest' => true,
        ]);

        $this->assertEquals(2, DocumentChunk::where('document_id', $document->id)->count());
    }

    public function test_structured_extraction_fields_are_persisted(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $document = Document::factory()->for($user)->uploaded()->create([
            'storage_path' => 'documents/test/doc.pdf',
        ]);

        Storage::disk('local')->put('documents/test/doc.pdf', '%PDF fake');

        $this->bindMockServices(
            extractionResult: ExtractionResult::success('Vertragstext', 1),
            classification: new ClassificationResult('mietvertrag', 0.8),
            fields: new ExtractionFields(
                title: 'Mietvertrag Musterstr. 1',
                summary: 'Wohnraummietvertrag für 3 Zimmer Wohnung',
                contractNumber: 'MV-2024-001',
                startDate: '2024-01-01',
                endDate: '2025-12-31',
                cancellationPeriod: '3 Monate',
            ),
            chunks: []
        );

        ProcessDocumentJob::dispatchSync($document->id);

        $document->refresh();
        $this->assertEquals('Mietvertrag Musterstr. 1', $document->title);
        $this->assertEquals('MV-2024-001', $document->contract_number);
        $this->assertEquals('3 Monate', $document->cancellation_period);
    }

    public function test_text_extraction_failure_marks_document_as_failed(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $document = Document::factory()->for($user)->uploaded()->create([
            'storage_path' => 'documents/test/broken.pdf',
        ]);

        Storage::disk('local')->put('documents/test/broken.pdf', '');

        $extractor = Mockery::mock(TextExtractorInterface::class);
        $extractor->shouldReceive('extract')
            ->once()
            ->andReturn(ExtractionResult::failure('text_extraction_failed'));

        $this->app->instance(TextExtractorInterface::class, $extractor);

        ProcessDocumentJob::dispatchSync($document->id);

        $document->refresh();
        $this->assertEquals('failed', $document->status);
        $this->assertEquals('text_extraction_failed', $document->processing_error);
    }

    public function test_failed_extraction_records_audit_log_with_error_code_only(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $document = Document::factory()->for($user)->uploaded()->create([
            'storage_path' => 'documents/test/encrypted.pdf',
        ]);

        Storage::disk('local')->put('documents/test/encrypted.pdf', '%PDF encrypted');

        $extractor = Mockery::mock(TextExtractorInterface::class);
        $extractor->shouldReceive('extract')
            ->once()
            ->andReturn(ExtractionResult::failure('encrypted_file'));

        $this->app->instance(TextExtractorInterface::class, $extractor);

        ProcessDocumentJob::dispatchSync($document->id);

        $audit = AuditLog::where('action', 'document.processing_failed')
            ->where('entity_id', $document->id)
            ->first();

        $this->assertNotNull($audit);
        $meta = $audit->meta_json;

        // Only error_code in audit log, no document content
        $this->assertArrayHasKey('error_code', $meta);
        $this->assertEquals('encrypted_file', $meta['error_code']);
        $this->assertArrayNotHasKey('raw_text', $meta);
        $this->assertArrayNotHasKey('content', $meta);
        $this->assertArrayNotHasKey('text', $meta);
    }

    public function test_job_is_idempotent_on_rerun(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $document = Document::factory()->for($user)->uploaded()->create([
            'storage_path' => 'documents/test/rerun.pdf',
        ]);

        Storage::disk('local')->put('documents/test/rerun.pdf', '%PDF content');

        $this->bindMockServices(
            extractionResult: ExtractionResult::success('Text', 1),
            classification: new ClassificationResult('unknown', 0.5),
            fields: new ExtractionFields(title: 'Test'),
            chunks: [
                new ChunkItem(chunkIndex: 0, chunkText: 'Text', pageReference: 1, tokenCount: 5),
            ]
        );

        // Run twice
        ProcessDocumentJob::dispatchSync($document->id);
        ProcessDocumentJob::dispatchSync($document->id);

        // Chunks should not be duplicated (idempotent delete+recreate)
        $this->assertEquals(1, DocumentChunk::where('document_id', $document->id)->count());

        // Only one latest structured_data record
        $this->assertEquals(1, \App\Models\DocumentStructuredData::where('document_id', $document->id)
            ->where('is_latest', true)
            ->count());
    }

    private function bindMockServices(
        ExtractionResult $extractionResult,
        ClassificationResult $classification,
        ExtractionFields $fields,
        array $chunks
    ): void {
        $extractor = Mockery::mock(TextExtractorInterface::class);
        $extractor->shouldReceive('extract')->andReturn($extractionResult);
        $this->app->instance(TextExtractorInterface::class, $extractor);

        $classifier = Mockery::mock(DocumentClassifierInterface::class);
        $classifier->shouldReceive('classify')->andReturn($classification);
        $this->app->instance(DocumentClassifierInterface::class, $classifier);

        $structuredExtractor = Mockery::mock(StructuredExtractorInterface::class);
        $structuredExtractor->shouldReceive('extract')->andReturn($fields);
        $this->app->instance(StructuredExtractorInterface::class, $structuredExtractor);

        $chunker = Mockery::mock(ChunkerInterface::class);
        $chunker->shouldReceive('chunk')->andReturn($chunks);
        $this->app->instance(ChunkerInterface::class, $chunker);
    }
}
