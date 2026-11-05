<?php

namespace App\Enums;

enum ConflictType: string
{
    case CharacterVsCharacter = 'character_vs_character';
    case CharacterVsSociety = 'character_vs_society';
    case CharacterVsNature = 'character_vs_nature';
    case CharacterVsSelf = 'character_vs_self';

    public function label(): string
    {
        return match ($this) {
            self::CharacterVsCharacter => 'Character vs. Character',
            self::CharacterVsSociety => 'Character vs. Society',
            self::CharacterVsNature => 'Character vs. Nature',
            self::CharacterVsSelf => 'Character vs. Self',
        };
    }

    public function subtitle(): string
    {
        return match ($this) {
            self::CharacterVsCharacter => 'Interpersonal conflict between individuals',
            self::CharacterVsSociety => 'Rules, laws, customs & community institutions',
            self::CharacterVsNature => 'Weather, wilderness & environmental forces',
            self::CharacterVsSelf => 'Internal doubts, fear & moral dilemmas',
        };
    }

    public function colorKey(): string
    {
        return match ($this) {
            self::CharacterVsCharacter => 'red',
            self::CharacterVsSociety => 'blue',
            self::CharacterVsNature => 'green',
            self::CharacterVsSelf => 'amber',
        };
    }

    public function colorHex(): string
    {
        return match ($this) {
            self::CharacterVsCharacter => '#EF4444',
            self::CharacterVsSociety => '#3B82F6',
            self::CharacterVsNature => '#10B981',
            self::CharacterVsSelf => '#F59E0B',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CharacterVsCharacter => '🔴',
            self::CharacterVsSociety => '🔵',
            self::CharacterVsNature => '🟢',
            self::CharacterVsSelf => '🟡',
        };
    }
}
