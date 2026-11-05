<?php

namespace App\Enums;

enum QuizType: string
{
    case MultipleChoice = 'multiple_choice';
    case ColorTheConflict = 'color_the_conflict';
    case TheConflictMap = 'the_conflict_map';
    case MatchingType = 'matching_type';
    case Evaluation = 'evaluation';
    case Reflection = 'reflection';

    /**
     * Get a human-readable label for the quiz type.
     */
    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Multiple Choice',
            self::ColorTheConflict => 'Color the Conflict',
            self::TheConflictMap => 'The Conflict Map',
            self::MatchingType => 'Matching Type',
            self::Evaluation => 'Evaluation',
            self::Reflection => 'Reflection',
        };
    }

    /**
     * Get the default icon for the quiz type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::MultipleChoice => '🔘',
            self::ColorTheConflict => '🎨',
            self::TheConflictMap => '🗺️',
            self::MatchingType => '🧩',
            self::Evaluation => '📊',
            self::Reflection => '💭',
        };
    }
}
