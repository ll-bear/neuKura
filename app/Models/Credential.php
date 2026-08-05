<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bookmark_id',
        'label',
        'username',
        'password_encrypted',
    ];

    protected $hidden = [
        'password_encrypted',
    ];

    protected $casts = [
        // Laravel標準のencryptedキャストを使うと、モデル経由の読み書きで自動的に暗号化/復号される
        // $credential->password_encrypted = '生パスワード'; で保存時に自動暗号化
        // $credential->password_encrypted; で取得時に自動復号
        'password_encrypted' => 'encrypted',
    ];

    public function bookmark(): BelongsTo
    {
        return $this->belongsTo(Bookmark::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 拡張機能へのAPIレスポンス用に整形した配列を返す
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label ?? $this->bookmark->title,
            'username' => $this->username,
            'password' => $this->password_encrypted, // encryptedキャストにより取得時点で復号済み
        ];
    }
}
