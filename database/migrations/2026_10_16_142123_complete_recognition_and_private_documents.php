<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idea_contributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('share');
            $table->timestamps();
            $table->unique(['idea_id', 'user_id']);
        });
        Schema::create('award_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bonus_award_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unique(['bonus_award_id', 'user_id']);
        });
        Schema::create('idea_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
        Schema::create('cycle_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_cycle_id')->constrained()->restrictOnDelete();
            $table->foreignId('idea_id')->constrained()->restrictOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->text('notes');
            $table->timestamps();
            $table->unique(['voting_cycle_id', 'idea_id']);
        });
        Schema::create('privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->text('reason');
            $table->text('decision')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['privacy_requests', 'cycle_reviews', 'idea_attachments', 'award_allocations', 'idea_contributors'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
