<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
  {
    Schema::create('favorites', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->string('card_id');
      $table->foreign('card_id')->references('id')->on('cards')->onDelete('cascade');
      $table->timestamps();

      // 同じユーザーが同じカードを複数回お気に入りできないようにする
      $table->unique(['user_id', 'card_id']);
    });
  }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
