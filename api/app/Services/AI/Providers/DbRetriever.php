<?php

namespace App\Services\AI\Providers;

use App\Models\DocumentChunk;
use App\Services\AI\Contracts\RetrieverInterface;
use App\Services\AI\DTOs\ChunkItem;
use Illuminate\Support\Facades\DB;

class DbRetriever implements RetrieverInterface
{
    public function retrieve(
        string $query,
        string $userId,
        ?string $documentId = null,
        int $limit = 5
    ): array {
        $dbChunks = DocumentChunk::query()
            ->select('document_chunks.*', 'documents.title as document_title')
            ->join('documents', 'document_chunks.document_id', '=', 'documents.id')
            ->where('documents.user_id', $userId)
            ->when($documentId, fn ($q) => $q->where('document_chunks.document_id', $documentId))
            ->where('document_chunks.chunk_text', 'ilike', '%'.$query.'%')
            ->orderBy('document_chunks.chunk_index')
            ->limit($limit)
            ->get();

        return $dbChunks->map(fn ($chunk) => new ChunkItem(
            chunkIndex: $chunk->chunk_index,
            chunkText: $chunk->chunk_text,
            pageReference: $chunk->page_reference,
            tokenCount: $chunk->token_count ?? 0,
            documentId: $chunk->document_id,
            documentTitle: $chunk->document_title,
        ))->all();
    }
}
