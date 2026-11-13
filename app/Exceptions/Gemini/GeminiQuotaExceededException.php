<?php

namespace App\Exceptions\Gemini;

class GeminiQuotaExceededException extends GeminiException
{
    public function __construct(string $message = 'Google Gemini free-tier rate limit reached. Please wait a moment before trying again.')
    {
        parent::__construct($message);
    }
}
