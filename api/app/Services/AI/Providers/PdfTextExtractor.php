<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\TextExtractorInterface;
use App\Services\AI\DTOs\ExtractionResult;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class PdfTextExtractor implements TextExtractorInterface
{
    public function extract(string $storagePath): ExtractionResult
    {
        try {
            $disk = config('filesystems.document_disk', 'local');
            $content = Storage::disk($disk)->get($storagePath);
            if (empty($content)) {
                return ExtractionResult::failure('empty_content');
            }

            $parser = new Parser();
            $pdf = $parser->parseContent($content);
            $text = $pdf->getText();

            if (empty(trim($text))) {
                return ExtractionResult::failure('no_text_content');
            }

            $pages = $pdf->getPages();

            return ExtractionResult::success(
                rawText: $text,
                pageCount: count($pages)
            );
        } catch (\Exception $e) {
            $message = strtolower($e->getMessage());

            if (str_contains($message, 'encrypt')) {
                return ExtractionResult::failure('encrypted_file');
            }

            return ExtractionResult::failure('text_extraction_failed');
        }
    }
}
