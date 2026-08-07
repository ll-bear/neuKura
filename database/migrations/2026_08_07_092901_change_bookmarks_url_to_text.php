<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * doctrine/dbal 未導入でも動くよう、change()ではなく生SQLで変更する。
     * VARCHAR(255)ではOAuthコールバックURLなど長いURLが入らないためTEXTに拡張。
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `bookmarks` MODIFY `url` TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `bookmarks` MODIFY `url` VARCHAR(255) NOT NULL');
    }
};
