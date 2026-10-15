<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->index();
            $table->text('block_reason')->nullable();
            $table->unsignedInteger('access_version')->default(0);
        });
        Schema::table('sectors', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
            $table->unique('name');
        });
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->restrictOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unique(['team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');
        Schema::table('sectors', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn('is_active');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['blocked_at', 'block_reason', 'access_version']));
    }
};
