<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Secret extends Model
{
    const CATEGORIES = ['wifi', 'license', 'pin', 'uncategorized', 'other'];

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'fields',
        'memo',
    ];

    protected $casts = [
        'fields' => 'encrypted:array', // 例: {"ssid": "...", "password": "..."}
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
