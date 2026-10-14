<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('idea_results', fn (Blueprint $table) => $table->unique('idea_id'));
        Schema::table('implementation_plans', fn (Blueprint $table) => $table->unique('idea_id'));
    }

    public function down(): void
    {
        Schema::table('idea_results', fn (Blueprint $table) => $table->dropUnique(['idea_id']));
        Schema::table('implementation_plans', fn (Blueprint $table) => $table->dropUnique(['idea_id']));
    }
};
