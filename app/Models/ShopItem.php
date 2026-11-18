<?php

namespace App\Models;

use App\Enums\ShopItemCategory;
use Database\Factories\ShopItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A product students can buy with gems in the Gem Bazaar.
 */
#[Fillable([
    'key',
    'name',
    'category',
    'rarity',
    'description',
    'perk',
    'price',
    'min_level',
    'max_quantity',
    'is_active',
    'sort_order',
    'metadata',
])]
class ShopItem extends Model
{
    /** @use HasFactory<ShopItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ShopItemCategory::class,
            'price' => 'integer',
            'min_level' => 'integer',
            'max_quantity' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * The route key used for shop purchase and equip URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * Limit the query to items currently on sale.
     *
     * @param  Builder<ShopItem>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Every purchase made of this item.
     *
     * @return HasMany<ShopPurchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(ShopPurchase::class);
    }
}
