<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category')->default('uncategorized'); // wifi / license / pin / uncategorized / other
            $table->string('title');
            $table->text('fields'); // encrypted:array キャストでJSON暗号化保存
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secrets');
    }
};
