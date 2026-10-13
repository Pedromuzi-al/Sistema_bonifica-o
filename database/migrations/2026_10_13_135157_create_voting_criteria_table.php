<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voting_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_cycle_id')->constrained('voting_cycles')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('weight', 5, 2)->default(1);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_criteria');
    }
};
