<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->string('idea_code')->unique();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('origin_sector_id')->constrained('sectors')->restrictOnDelete();
            $table->string('title');
            $table->text('problem_description');
            $table->text('solution_description');
            $table->text('expected_results');
            $table->text('resources_needed');
            $table->text('additional_information')->nullable();
            $table->string('status')->default('submitted');
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};
