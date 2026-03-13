<?php

namespace Tests\Unit;

use App\Services\AI\Providers\SimpleChunker;
use Tests\TestCase;

class SimpleChunkerTest extends TestCase
{
    private SimpleChunker $chunker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chunker = new SimpleChunker(chunkSize: 10, overlap: 2);
    }

    public function test_empty_text_returns_no_chunks(): void
    {
        $chunks = $this->chunker->chunk('');
        $this->assertEmpty($chunks);
    }

    public function test_short_text_produces_single_chunk(): void
    {
        $chunks = $this->chunker->chunk('Wort1 Wort2 Wort3');
        $this->assertCount(1, $chunks);
        $this->assertEquals(0, $chunks[0]->chunkIndex);
    }

    public function test_chunks_have_sequential_indexes(): void
    {
        $words = implode(' ', array_fill(0, 25, 'Wort'));
        $chunks = $this->chunker->chunk($words);

        foreach ($chunks as $i => $chunk) {
            $this->assertEquals($i, $chunk->chunkIndex);
        }
    }

    public function test_chunk_text_is_not_empty(): void
    {
        $text = implode(' ', array_fill(0, 30, 'Wort'));
        $chunks = $this->chunker->chunk($text);

        foreach ($chunks as $chunk) {
            $this->assertNotEmpty($chunk->chunkText);
        }
    }
}
