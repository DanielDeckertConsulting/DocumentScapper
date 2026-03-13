<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Datei-Metadaten
            $table->string('original_filename', 500);
            $table->string('mime_type', 100);
            $table->bigInteger('size_bytes');
            $table->text('storage_path');

            // Verarbeitungs-Status
            $table->string('status', 50)->default('uploaded');
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('extraction_version', 50)->nullable();

            // Extrahierte Kerndaten
            $table->string('document_type', 100)->nullable();
            $table->text('title')->nullable();
            $table->text('summary')->nullable();
            $table->longText('raw_text')->nullable();

            // Normalisierte Strukturfelder
            $table->text('counterparty_name')->nullable();
            $table->text('contract_holder_name')->nullable();
            $table->text('contract_number')->nullable();
            $table->text('policy_number')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('duration_text')->nullable();
            $table->text('cancellation_period')->nullable();
            $table->decimal('payment_amount', 15, 2)->nullable();
            $table->string('payment_currency', 10)->nullable();
            $table->string('payment_interval', 50)->nullable();
            $table->text('important_terms')->nullable();
            $table->text('exclusions')->nullable();
            $table->text('contact_details')->nullable();

            // Flexible Erweiterungsfelder
            $table->jsonb('custom_fields_json')->default('{}');

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
