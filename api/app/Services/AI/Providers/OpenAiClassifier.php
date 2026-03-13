<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\DocumentClassifierInterface;
use App\Services\AI\DTOs\ClassificationResult;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiClassifier implements DocumentClassifierInterface
{
    private const VALID_TYPES = [
        'haftpflichtversicherung',
        'hausratversicherung',
        'krankenversicherung',
        'lebensversicherung',
        'kfz_versicherung',
        'mietvertrag',
        'arbeitsvertrag',
        'allgemeiner_vertrag',
        'unknown',
    ];

    public function classify(string $rawText): ClassificationResult
    {
        $excerpt = mb_substr($rawText, 0, 2000);

        try {
            $response = OpenAI::chat()->create([
                'model' => config('ai.models.extraction'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Klassifiziere das folgende Dokument. Antworte ausschließlich mit einem JSON-Objekt: {"document_type": "<typ>", "confidence": <0.0-1.0>}. Erlaubte Typen: '.implode(', ', self::VALID_TYPES),
                    ],
                    [
                        'role' => 'user',
                        'content' => $excerpt,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 100,
            ]);

            $result = json_decode($response->choices[0]->message->content, true);
            $type = $result['document_type'] ?? 'unknown';

            if (! in_array($type, self::VALID_TYPES)) {
                $type = 'unknown';
            }

            return new ClassificationResult(
                documentType: $type,
                confidence: (float) ($result['confidence'] ?? 0.5),
            );
        } catch (\Throwable) {
            return new ClassificationResult(documentType: 'unknown', confidence: 0.0);
        }
    }
}
