<?php

namespace Tests\Feature\Chat;

use App\Models\ChatSession;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_users_chat_session(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $session = ChatSession::factory()->for($owner)->create();

        $this->actingAs($other)
            ->getJson("/api/chat-sessions/{$session->id}/messages")
            ->assertForbidden();
    }

    public function test_user_cannot_send_message_to_another_users_session(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $session = ChatSession::factory()->for($owner)->create();

        $this->actingAs($other)
            ->postJson("/api/chat-sessions/{$session->id}/messages", [
                'content' => 'Test-Frage',
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_create_session_for_another_users_document(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $document = Document::factory()->for($owner)->create();

        $this->actingAs($other)
            ->postJson('/api/chat-sessions', [
                'document_id' => $document->id,
            ])
            ->assertNotFound();
    }

    public function test_user_can_create_all_documents_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/chat-sessions', [
                'document_id' => null,
                'title' => 'Alle Dokumente',
            ])
            ->assertCreated()
            ->assertJsonPath('data.document_id', null);
    }
}
