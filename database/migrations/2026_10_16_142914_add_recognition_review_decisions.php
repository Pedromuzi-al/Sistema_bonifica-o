<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cycle_reviews', function (Blueprint $table) {
            $table->string('decision')->default('validated');
        });
        Schema::table('bonus_awards', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bonus_awards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn('rejected_at');
        });
        Schema::table('cycle_reviews', function (Blueprint $table) {
            $table->dropColumn('decision');
        });
    }
};
