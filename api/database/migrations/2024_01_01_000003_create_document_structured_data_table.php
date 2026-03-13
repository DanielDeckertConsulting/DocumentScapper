<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_structured_data', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->integer('extraction_run')->default(1);
            $table->string('extractor', 100)->default('openai-gpt4o-mini');
            $table->jsonb('raw_response')->nullable();
            $table->boolean('is_latest')->default(true);
            $table->timestamps();

            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_structured_data');
    }
};
