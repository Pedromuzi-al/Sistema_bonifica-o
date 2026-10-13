<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('voting_cycle_id')->constrained('voting_cycles')->cascadeOnDelete();
            $table->string('status')->default('cast');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['idea_id', 'user_id', 'voting_cycle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
