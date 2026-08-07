<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * このブックマークに紐づく認証情報(複数可)
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    /**
     * URLからホスト名を動的に算出するアクセサ
     */
    protected function domain(): Attribute
    {
        return Attribute::get(
            fn () => parse_url($this->url, PHP_URL_HOST)
        );
    }
}