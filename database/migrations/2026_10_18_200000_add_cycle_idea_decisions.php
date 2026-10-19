<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_idea_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_cycle_id')->constrained()->restrictOnDelete();
            $table->foreignId('idea_id')->constrained()->restrictOnDelete();
            $table->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $table->string('decision');
            $table->text('notes');
            $table->timestamps();
            $table->unique(['voting_cycle_id', 'idea_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_idea_decisions');
    }
};
