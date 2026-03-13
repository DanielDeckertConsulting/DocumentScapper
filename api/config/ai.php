<?php

return [
    'models' => [
        'extraction' => env('OPENAI_MODEL_EXTRACTION', 'gpt-4o-mini'),
        'chat' => env('OPENAI_MODEL_CHAT', 'gpt-4o-mini'),
    ],
    'chunker' => [
        'chunk_size' => env('CHUNKER_CHUNK_SIZE', 800),
        'overlap' => env('CHUNKER_OVERLAP', 100),
    ],
    'retrieval' => [
        'limit' => env('RETRIEVAL_LIMIT', 5),
    ],
];
