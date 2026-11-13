<?php

namespace App\Exceptions\Gemini;

class GeminiApiException extends GeminiException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 502,
        public readonly ?string $rawResponse = null
    ) {
        parent::__construct($message, $statusCode);
    }
}
