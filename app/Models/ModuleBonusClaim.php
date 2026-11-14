<?php

namespace App\Models;

use Database\Factories\ModuleBonusClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's one-time claim of a module's Grand Treasure Vault bonus XP.
 */
class ModuleBonusClaim extends Model
{
    /** @use HasFactory<ModuleBonusClaimFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'module_id',
        'xp_awarded',
        'claimed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'xp_awarded' => 'integer',
        'claimed_at' => 'datetime',
    ];

    /**
     * The student who opened the vault.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The module whose vault was opened.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
