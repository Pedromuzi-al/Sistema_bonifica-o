<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vote_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vote_id')->constrained('votes')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('voting_criteria')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['vote_id', 'criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_scores');
    }
};
