<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\StructuredExtractorInterface;
use App\Services\AI\DTOs\ExtractionFields;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiStructuredExtractor implements StructuredExtractorInterface
{
    private const MAX_TEXT_CHARS = 24000;

    public function extract(string $rawText, string $documentType): ExtractionFields
    {
        $truncated = mb_substr($rawText, 0, self::MAX_TEXT_CHARS);

        $schema = $this->buildSchema();

        try {
            $response = OpenAI::chat()->create([
                'model' => config('ai.models.extraction'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => <<<PROMPT
Extrahiere strukturierte Daten aus diesem Dokument (Typ: {$documentType}).
Antworte ausschließlich mit einem validen JSON-Objekt gemäß dem Schema.
Fehlende Informationen = null. Erfinde keine Werte. Keine Rechtsberatung.
PROMPT,
                    ],
                    ['role' => 'user', 'content' => $truncated],
                ],
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 1500,
            ]);

            $data = json_decode($response->choices[0]->message->content, true) ?? [];

            return $this->mapToFields($data);
        } catch (\Throwable) {
            return new ExtractionFields();
        }
    }

    private function mapToFields(array $data): ExtractionFields
    {
        return new ExtractionFields(
            title: $data['title'] ?? null,
            summary: $data['summary'] ?? null,
            counterpartyName: $data['counterparty_name'] ?? null,
            contractHolderName: $data['contract_holder_name'] ?? null,
            contractNumber: $data['contract_number'] ?? null,
            policyNumber: $data['policy_number'] ?? null,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            durationText: $data['duration_text'] ?? null,
            cancellationPeriod: $data['cancellation_period'] ?? null,
            paymentAmount: isset($data['payment_amount']) ? (float) $data['payment_amount'] : null,
            paymentCurrency: $data['payment_currency'] ?? null,
            paymentInterval: $data['payment_interval'] ?? null,
            importantTerms: $data['important_terms'] ?? null,
            exclusions: $data['exclusions'] ?? null,
            contactDetails: $data['contact_details'] ?? null,
            customFieldsJson: $data['custom_fields'] ?? [],
        );
    }

    private function buildSchema(): array
    {
        return [
            'title', 'summary', 'counterparty_name', 'contract_holder_name',
            'contract_number', 'policy_number', 'start_date', 'end_date',
            'duration_text', 'cancellation_period', 'payment_amount',
            'payment_currency', 'payment_interval', 'important_terms',
            'exclusions', 'contact_details', 'custom_fields',
        ];
    }
}
