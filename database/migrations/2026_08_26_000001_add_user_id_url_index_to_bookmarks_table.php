<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * urlカラムがTEXT型のため、MySQLでは索引作成時にプレフィックス長の指定が必須
     * (TEXT/BLOBは可変長で上限が大きく、フルカラムでのB-Treeインデックスを作れないため)。
     * ドメイン絞り込みは "https://example.com" のような先頭〜ホスト部分の一致で
     * 十分なため、191文字のプレフィックスで足りる(utf8mb4でも安全な長さ)。
     */
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE bookmarks ADD INDEX bookmarks_user_id_url_index (user_id, url(191))'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE bookmarks DROP INDEX bookmarks_user_id_url_index'
        );
    }
};
