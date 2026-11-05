<?php

namespace App\Enums;

enum QuestionSelectionType: string
{
    case Single = 'single';
    case Multiple = 'multiple';

    /**
     * Get a human-readable label for the selection type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single Choice (One correct answer)',
            self::Multiple => 'Multi-Select (Multiple correct answers)',
        };
    }
}
