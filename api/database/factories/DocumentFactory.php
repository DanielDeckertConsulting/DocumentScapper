<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(10000, 5000000),
            'storage_path' => 'documents/'.fake()->uuid().'/'.fake()->uuid().'.pdf',
            'status' => 'processed',
            'document_type' => 'allgemeiner_vertrag',
            'title' => fake('de_DE')->sentence(4),
            'summary' => fake('de_DE')->paragraph(),
        ];
    }

    public function uploaded(): static
    {
        return $this->state(['status' => 'uploaded']);
    }

    public function processing(): static
    {
        return $this->state(['status' => 'processing']);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'failed', 'processing_error' => 'text_extraction_failed']);
    }
}
