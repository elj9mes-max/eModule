<?php

namespace App\Enums;

enum ShopItemCategory: string
{
    case Avatar = 'avatar';
    case Background = 'background';
    case Console = 'console';
    case Booster = 'booster';

    /**
     * Whether items in this category can be worn or applied to the student's look.
     */
    public function isEquippable(): bool
    {
        return $this !== self::Booster;
    }

    /**
     * The student profile column holding the equipped item for this category.
     */
    public function equippedColumn(): ?string
    {
        return match ($this) {
            self::Avatar => 'equipped_avatar_item_id',
            self::Background => 'equipped_background_item_id',
            self::Console => 'equipped_console_item_id',
            self::Booster => null,
        };
    }
}
