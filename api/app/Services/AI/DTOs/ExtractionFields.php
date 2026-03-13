<?php

namespace App\Services\AI\DTOs;

class ExtractionFields
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $summary = null,
        public readonly ?string $counterpartyName = null,
        public readonly ?string $contractHolderName = null,
        public readonly ?string $contractNumber = null,
        public readonly ?string $policyNumber = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?string $durationText = null,
        public readonly ?string $cancellationPeriod = null,
        public readonly ?float $paymentAmount = null,
        public readonly ?string $paymentCurrency = null,
        public readonly ?string $paymentInterval = null,
        public readonly ?string $importantTerms = null,
        public readonly ?string $exclusions = null,
        public readonly ?string $contactDetails = null,
        public readonly array $customFieldsJson = [],
    ) {}

    public function toDocumentAttributes(): array
    {
        return array_filter([
            'title' => $this->title,
            'summary' => $this->summary,
            'counterparty_name' => $this->counterpartyName,
            'contract_holder_name' => $this->contractHolderName,
            'contract_number' => $this->contractNumber,
            'policy_number' => $this->policyNumber,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'duration_text' => $this->durationText,
            'cancellation_period' => $this->cancellationPeriod,
            'payment_amount' => $this->paymentAmount,
            'payment_currency' => $this->paymentCurrency,
            'payment_interval' => $this->paymentInterval,
            'important_terms' => $this->importantTerms,
            'exclusions' => $this->exclusions,
            'contact_details' => $this->contactDetails,
            'custom_fields_json' => $this->customFieldsJson ?: null,
        ], fn ($v) => $v !== null);
    }
}
