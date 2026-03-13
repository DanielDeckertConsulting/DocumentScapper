<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 500)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
