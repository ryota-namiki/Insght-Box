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
        Schema::create('board_cards', function (Blueprint $table) {
            $table->id();
            $table->uuid('board_id');
            $table->uuid('card_id');
            $table->integer('position_x')->nullable();
            $table->integer('position_y')->nullable();
            $table->timestamps();
            
            $table->foreign('board_id')->references('id')->on('boards')->onDelete('cascade');
            $table->foreign('card_id')->references('id')->on('cards')->onDelete('cascade');
            
            // 同じカードを同じボードに複数回追加できないようにする
            $table->unique(['board_id', 'card_id']);
            
            $table->index('board_id');
            $table->index('card_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_cards');
    }
};
