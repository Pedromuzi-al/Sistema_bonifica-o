<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idea_beneficiary_sectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignId('sector_id')->constrained('sectors')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['idea_id', 'sector_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idea_beneficiary_sectors');
    }
};
