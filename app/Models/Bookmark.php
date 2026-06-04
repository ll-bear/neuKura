<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'url',
        'title', 'image_url', 'memo', 'summary', 'vector',
    ];

    protected $casts = ['vector' => 'array'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
