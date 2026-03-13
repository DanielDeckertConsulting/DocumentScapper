<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ChatAnswererInterface;
use App\Services\AI\DTOs\ChatRequest;
use App\Services\AI\DTOs\ChatResponse;
use App\Services\AI\DTOs\ChunkItem;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiChatAnswerer implements ChatAnswererInterface
{
    public function answer(ChatRequest $request): ChatResponse
    {
        $systemPrompt = $this->buildSystemPrompt();
        $contextBlock = $this->buildContextBlock($request->documentContexts, $request->chunks);
        $messages = $this->buildMessages($systemPrompt, $contextBlock, $request->chatHistory, $request->question);

        try {
            $response = OpenAI::chat()->create([
                'model' => config('ai.models.chat'),
                'messages' => $messages,
                'max_tokens' => 1000,
                'temperature' => 0.1,
            ]);

            $answer = $response->choices[0]->message->content;
            $citations = $this->buildCitations($request->chunks);

            return new ChatResponse(answer: $answer, citations: $citations);
        } catch (\Throwable $e) {
            return new ChatResponse(
                answer: 'Entschuldigung, die Anfrage konnte nicht verarbeitet werden. Bitte versuchen Sie es erneut.',
                citations: []
            );
        }
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
Du bist ein Dokumentenassistent. Beantworte Fragen AUSSCHLIESSLICH basierend auf dem bereitgestellten Dokumentkontext.

Regeln:
1. Antworte nur auf Basis der bereitgestellten Dokumentinhalte.
2. Wenn die Information nicht im Kontext enthalten ist, sage: "Diese Information ist in den vorliegenden Dokumenten nicht enthalten."
3. Gib keine Rechtsberatung und erfinde keine Fakten.
4. Verweise auf die relevanten Dokumentabschnitte in deiner Antwort.
5. Sei präzise und verständlich. Antworte auf Deutsch.
PROMPT;
    }

    private function buildContextBlock(array $documentContexts, array $chunks): string
    {
        $lines = ["=== Dokumentkontext ===\n"];

        foreach ($documentContexts as $doc) {
            $lines[] = "Dokument: {$doc['title']} (Typ: {$doc['document_type']})";
            if (! empty($doc['summary'])) {
                $lines[] = "Zusammenfassung: {$doc['summary']}";
            }
            if (! empty($doc['counterparty_name'])) {
                $lines[] = "Vertragspartner: {$doc['counterparty_name']}";
            }
            if (! empty($doc['cancellation_period'])) {
                $lines[] = "Kündigungsfrist: {$doc['cancellation_period']}";
            }
            if (! empty($doc['payment_amount'])) {
                $lines[] = "Beitrag: {$doc['payment_amount']} {$doc['payment_currency']} ({$doc['payment_interval']})";
            }
            $lines[] = '';
        }

        /** @var ChunkItem $chunk */
        foreach ($chunks as $chunk) {
            $pageRef = $chunk->pageReference ? " (Seite {$chunk->pageReference})" : '';
            $lines[] = "[Abschnitt {$chunk->chunkIndex}{$pageRef}]: {$chunk->chunkText}";
        }

        return implode("\n", $lines);
    }

    private function buildMessages(
        string $systemPrompt,
        string $contextBlock,
        array $history,
        string $question
    ): array {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $contextBlock],
            ['role' => 'assistant', 'content' => 'Ich habe den Dokumentkontext erhalten und bin bereit, Ihre Fragen zu beantworten.'],
        ];

        foreach (array_slice($history, -3) as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        return $messages;
    }

    private function buildCitations(array $chunks): array
    {
        return array_map(fn (ChunkItem $chunk) => [
            'document_id' => $chunk->documentId,
            'document_title' => $chunk->documentTitle,
            'chunk_index' => $chunk->chunkIndex,
            'page_reference' => $chunk->pageReference,
            'excerpt' => mb_substr($chunk->chunkText, 0, 200),
        ], $chunks);
    }
}
