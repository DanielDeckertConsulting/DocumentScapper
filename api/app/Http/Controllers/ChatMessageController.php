<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Document;
use App\Services\AI\Contracts\ChatAnswererInterface;
use App\Services\AI\Contracts\RetrieverInterface;
use App\Services\AI\DTOs\ChatRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
    public function __construct(
        private readonly RetrieverInterface $retriever,
        private readonly ChatAnswererInterface $chatAnswerer,
    ) {}

    public function index(Request $request, ChatSession $chatSession): JsonResponse
    {
        $this->authorize('view', $chatSession);

        $messages = $chatSession->messages()
            ->get()
            ->map(fn ($m) => $this->toResource($m));

        return response()->json(['data' => $messages]);
    }

    public function store(StoreChatMessageRequest $request, ChatSession $chatSession): JsonResponse
    {
        $this->authorize('view', $chatSession);

        $userId = $request->user()->id;
        $question = $request->content;

        // Store user message
        ChatMessage::create([
            'chat_session_id' => $chatSession->id,
            'role' => 'user',
            'content' => $question,
            'citations_json' => [],
        ]);

        // Retrieve relevant chunks
        $chunks = $this->retriever->retrieve(
            query: $question,
            userId: $userId,
            documentId: $chatSession->document_id,
            limit: 5
        );

        // Build document contexts
        $documentContexts = $this->buildDocumentContexts($chatSession, $userId);

        // Fetch chat history (last 3 exchanges)
        $history = $chatSession->messages()
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();

        // Generate answer
        $chatRequest = new ChatRequest(
            question: $question,
            documentContexts: $documentContexts,
            chunks: $chunks,
            chatHistory: $history,
        );

        $chatResponse = $this->chatAnswerer->answer($chatRequest);

        // Store assistant response
        $assistantMessage = ChatMessage::create([
            'chat_session_id' => $chatSession->id,
            'role' => 'assistant',
            'content' => $chatResponse->answer,
            'citations_json' => $chatResponse->citations,
        ]);

        AuditLog::record(
            action: 'chat_message.sent',
            userId: $userId,
            entityType: 'chat_session',
            entityId: $chatSession->id,
        );

        return response()->json(['data' => $this->toResource($assistantMessage)], 201);
    }

    private function buildDocumentContexts(ChatSession $session, string $userId): array
    {
        $query = Document::where('user_id', $userId)
            ->where('status', 'processed');

        if ($session->document_id) {
            $query->where('id', $session->document_id);
        }

        return $query->get()->map(fn ($doc) => [
            'id' => $doc->id,
            'title' => $doc->title ?? $doc->original_filename,
            'document_type' => $doc->document_type ?? 'unknown',
            'summary' => $doc->summary,
            'counterparty_name' => $doc->counterparty_name,
            'cancellation_period' => $doc->cancellation_period,
            'payment_amount' => $doc->payment_amount,
            'payment_currency' => $doc->payment_currency,
            'payment_interval' => $doc->payment_interval,
        ])->all();
    }

    private function toResource(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'citations_json' => $message->citations_json ?? [],
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}
