<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\StoreDocumentRequest;
use App\Jobs\ProcessDocumentJob;
use App\Models\AuditLog;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = Document::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($doc) => $this->toListResource($doc));

        return response()->json(['data' => $documents]);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $userId = $request->user()->id;

        $extension = $file->getClientOriginalExtension();
        $storagePath = 'documents/'.$userId.'/'.Str::uuid().'.'.$extension;

        Storage::disk(config('filesystems.document_disk', 'local'))->putFileAs(
            dirname($storagePath),
            $file,
            basename($storagePath)
        );

        $document = Document::create([
            'user_id' => $userId,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'storage_path' => $storagePath,
            'status' => 'uploaded',
        ]);

        AuditLog::record(
            action: 'document.upload_initiated',
            userId: $userId,
            entityType: 'document',
            entityId: $document->id,
        );

        ProcessDocumentJob::dispatch($document->id);

        return response()->json(['data' => $this->toListResource($document)], 201);
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json(['data' => $this->toDetailResource($document)]);
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        $this->authorize('delete', $document);

        Storage::disk(config('filesystems.document_disk', 'local'))
            ->delete($document->storage_path);

        AuditLog::record(
            action: 'document.deleted',
            userId: $request->user()->id,
            entityType: 'document',
            entityId: $document->id,
        );

        $document->delete();

        return response()->json(null, 204);
    }

    private function toListResource(Document $doc): array
    {
        return [
            'id' => $doc->id,
            'original_filename' => $doc->original_filename,
            'mime_type' => $doc->mime_type,
            'size_bytes' => $doc->size_bytes,
            'status' => $doc->status,
            'document_type' => $doc->document_type,
            'title' => $doc->title,
            'summary' => $doc->summary,
            'processed_at' => $doc->processed_at?->toIso8601String(),
            'created_at' => $doc->created_at->toIso8601String(),
        ];
    }

    private function toDetailResource(Document $doc): array
    {
        return array_merge($this->toListResource($doc), [
            'extraction_version' => $doc->extraction_version,
            'processing_error' => $doc->processing_error,
            'counterparty_name' => $doc->counterparty_name,
            'contract_holder_name' => $doc->contract_holder_name,
            'contract_number' => $doc->contract_number,
            'policy_number' => $doc->policy_number,
            'start_date' => $doc->start_date?->toDateString(),
            'end_date' => $doc->end_date?->toDateString(),
            'duration_text' => $doc->duration_text,
            'cancellation_period' => $doc->cancellation_period,
            'payment_amount' => $doc->payment_amount,
            'payment_currency' => $doc->payment_currency,
            'payment_interval' => $doc->payment_interval,
            'important_terms' => $doc->important_terms,
            'exclusions' => $doc->exclusions,
            'contact_details' => $doc->contact_details,
            'custom_fields_json' => $doc->custom_fields_json ?? [],
        ]);
    }
}
