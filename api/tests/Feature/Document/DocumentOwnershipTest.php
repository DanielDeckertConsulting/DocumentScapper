<?php

namespace Tests\Feature\Document;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_own_documents(): void
    {
        $user = User::factory()->create();
        Document::factory()->count(3)->for($user)->create();

        $this->actingAs($user)
            ->getJson('/api/documents')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_cannot_see_other_users_documents(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Document::factory()->count(2)->for($owner)->create();

        $this->actingAs($other)
            ->getJson('/api/documents')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_user_cannot_view_another_users_document(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $document = Document::factory()->for($owner)->create();

        $this->actingAs($other)
            ->getJson("/api/documents/{$document->id}")
            ->assertForbidden();
    }

    public function test_user_cannot_delete_another_users_document(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $document = Document::factory()->for($owner)->create();

        $this->actingAs($other)
            ->deleteJson("/api/documents/{$document->id}")
            ->assertForbidden();
    }

    public function test_user_can_delete_own_document(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->for($user)->create([
            'storage_path' => 'documents/test/test.pdf',
        ]);

        \Illuminate\Support\Facades\Storage::fake();

        $this->actingAs($user)
            ->deleteJson("/api/documents/{$document->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }
}
