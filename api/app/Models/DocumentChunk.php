<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentChunk extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'document_id',
        'chunk_index',
        'chunk_text',
        'page_reference',
        'token_count',
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'page_reference' => 'integer',
            'token_count' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
