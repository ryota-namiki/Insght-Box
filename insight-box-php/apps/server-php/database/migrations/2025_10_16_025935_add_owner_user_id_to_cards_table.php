<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('cards', function (Blueprint $table) {
      $table->foreignId('owner_user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
      $table->unsignedBigInteger('team_id')->nullable()->after('owner_user_id');
      $table->enum('visibility', ['private', 'team'])->default('private')->after('team_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('cards', function (Blueprint $table) {
      $table->dropForeign(['owner_user_id']);
      $table->dropColumn(['owner_user_id', 'team_id', 'visibility']);
    });
  }
};
