<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // favicon はサイト(URL)単位の情報のためbookmarksに持たせる。
        // 同一bookmarkに複数credentialsがぶら下がる場合の重複取得を避けるため。
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->string('favicon_path')->nullable()->after('image_url');
            $table->timestamp('favicon_fetched_at')->nullable()->after('favicon_path');
        });
    }

    public function down(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropColumn(['favicon_path', 'favicon_fetched_at']);
        });
    }
};
