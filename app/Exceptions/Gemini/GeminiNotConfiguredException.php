<?php

namespace App\Exceptions\Gemini;

class GeminiNotConfiguredException extends GeminiException
{
    public function __construct(string $message = 'Google Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.')
    {
        parent::__construct($message);
    }
}
