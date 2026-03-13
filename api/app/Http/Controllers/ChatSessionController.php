<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chat\StoreChatSessionRequest;
use App\Models\AuditLog;
use App\Models\ChatSession;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sessions = ChatSession::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($s) => $this->toResource($s));

        return response()->json(['data' => $sessions]);
    }

    public function store(StoreChatSessionRequest $request): JsonResponse
    {
        $userId = $request->user()->id;

        if ($request->document_id) {
            $document = Document::where('id', $request->document_id)
                ->where('user_id', $userId)
                ->firstOrFail();
        }

        $session = ChatSession::create([
            'user_id' => $userId,
            'document_id' => $request->document_id ?? null,
            'title' => $request->title,
        ]);

        AuditLog::record(
            action: 'chat_session.created',
            userId: $userId,
            entityType: 'chat_session',
            entityId: $session->id,
        );

        return response()->json(['data' => $this->toResource($session)], 201);
    }

    public function show(Request $request, ChatSession $chatSession): JsonResponse
    {
        $this->authorize('view', $chatSession);

        return response()->json(['data' => $this->toResource($chatSession)]);
    }

    public function destroy(Request $request, ChatSession $chatSession): JsonResponse
    {
        $this->authorize('delete', $chatSession);
        $chatSession->delete();

        return response()->json(null, 204);
    }

    private function toResource(ChatSession $session): array
    {
        return [
            'id' => $session->id,
            'document_id' => $session->document_id,
            'title' => $session->title,
            'created_at' => $session->created_at->toIso8601String(),
        ];
    }
}
