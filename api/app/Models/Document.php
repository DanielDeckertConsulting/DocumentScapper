<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'original_filename',
        'mime_type',
        'size_bytes',
        'storage_path',
        'status',
        'processing_error',
        'processed_at',
        'extraction_version',
        'document_type',
        'title',
        'summary',
        'raw_text',
        'counterparty_name',
        'contract_holder_name',
        'contract_number',
        'policy_number',
        'start_date',
        'end_date',
        'duration_text',
        'cancellation_period',
        'payment_amount',
        'payment_currency',
        'payment_interval',
        'important_terms',
        'exclusions',
        'contact_details',
        'custom_fields_json',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
            'payment_amount' => 'decimal:2',
            'custom_fields_json' => 'array',
            'size_bytes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class)->orderBy('chunk_index');
    }

    public function structuredData(): HasMany
    {
        return $this->hasMany(DocumentStructuredData::class);
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
            'processing_error' => null,
        ]);
    }

    public function markAsFailed(string $errorCode): void
    {
        $this->update([
            'status' => 'failed',
            'processing_error' => $errorCode,
        ]);
    }
}
