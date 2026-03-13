<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentStructuredData extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'document_id',
        'extraction_run',
        'extractor',
        'raw_response',
        'is_latest',
    ];

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
            'is_latest' => 'boolean',
            'extraction_run' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
