<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('reward_amount', 12, 2)->default(0);
            $table->decimal('minimum_score', 5, 2)->default(0);
            $table->boolean('allow_abstention')->default(false);
            $table->unsignedInteger('anomaly_threshold')->default(5);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_policies');
    }
};
