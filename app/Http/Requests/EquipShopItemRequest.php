<?php

namespace App\Http\Requests;

use App\Models\ShopItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EquipShopItemRequest extends FormRequest
{
    /**
     * Only students may equip items, and only items that are on sale.
     */
    public function authorize(): bool
    {
        $shopItem = $this->route('shopItem');

        return ($this->user()?->isStudent() ?? false)
            && $shopItem instanceof ShopItem
            && $shopItem->is_active;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
