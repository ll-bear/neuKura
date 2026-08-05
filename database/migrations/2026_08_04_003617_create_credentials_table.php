<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bookmark_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('username');
            $table->text('password_encrypted'); // Laravelのencryptedキャストで暗号化して保存
            $table->timestamps();

            // 1ブックマークに複数アカウントを許容するため bookmark_id 単体のuniqueは付けない
            $table->index('bookmark_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
