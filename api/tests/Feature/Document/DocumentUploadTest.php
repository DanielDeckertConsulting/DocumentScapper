<?php

namespace Tests\Feature\Document;

use App\Jobs\ProcessDocumentJob;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_pdf(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('contract.pdf', 512, 'application/pdf');

        $response = $this->actingAs($user)
            ->postJson('/api/documents', ['file' => $file]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'uploaded')
            ->assertJsonPath('data.original_filename', 'contract.pdf');

        $this->assertDatabaseHas('documents', [
            'user_id' => $user->id,
            'original_filename' => 'contract.pdf',
            'status' => 'uploaded',
        ]);

        Queue::assertPushed(ProcessDocumentJob::class);
    }

    public function test_upload_stores_file_in_user_namespaced_path(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('policy.pdf', 100, 'application/pdf');

        $this->actingAs($user)->postJson('/api/documents', ['file' => $file]);

        $document = \App\Models\Document::where('user_id', $user->id)->first();
        $this->assertStringStartsWith("documents/{$user->id}/", $document->storage_path);
        Storage::disk('local')->assertExists($document->storage_path);
    }

    public function test_upload_records_audit_log_without_document_content(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $this->actingAs($user)->postJson('/api/documents', ['file' => $file]);

        $audit = AuditLog::where('user_id', $user->id)
            ->where('action', 'document.upload_initiated')
            ->first();

        $this->assertNotNull($audit);

        $meta = $audit->meta_json ?? [];
        $metaString = json_encode($meta);

        // Audit log must not contain raw document content
        $this->assertStringNotContainsString('pdf', strtolower($metaString));
        $this->assertStringNotContainsString('text', strtolower($metaString));
        $this->assertEmpty($meta, 'Audit log meta should contain only safe metadata, not document content');
    }

    public function test_non_pdf_file_is_rejected(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->actingAs($user)
            ->postJson('/api/documents', ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        Queue::assertNothingPushed();
    }

    public function test_txt_file_is_rejected(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

        $this->actingAs($user)
            ->postJson('/api/documents', ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_file_exceeding_20mb_is_rejected(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        // 21 MB file
        $file = UploadedFile::fake()->create('large.pdf', 21 * 1024, 'application/pdf');

        $this->actingAs($user)
            ->postJson('/api/documents', ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_requires_authentication(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $this->postJson('/api/documents', ['file' => $file])
            ->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    public function test_upload_without_file_returns_validation_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/documents', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }
}
