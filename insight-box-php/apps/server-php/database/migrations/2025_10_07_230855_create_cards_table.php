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
        Schema::create('cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Summary fields
            $table->string('title', 200);
            $table->string('company', 200)->nullable();
            $table->json('tags')->nullable();
            $table->string('event_id');
            $table->string('author_id')->nullable();
            $table->string('status', 50)->default('draft');
            
            // Detail fields
            $table->text('memo')->nullable();
            $table->text('ocr_text')->nullable();
            $table->text('raw_text')->nullable();
            $table->string('document_id')->nullable();
            $table->longText('camera_image')->nullable();
            
            // Reactions
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('views')->default(0);
            
            // Position
            $table->integer('position_x')->nullable();
            $table->integer('position_y')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('event_id');
            $table->index('author_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
