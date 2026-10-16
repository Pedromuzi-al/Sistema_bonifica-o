<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('invitation_pending')->default(false));
        Schema::create('administration_locks', fn (Blueprint $table) => $table->unsignedInteger('id')->primary());
        DB::table('administration_locks')->insert(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('administration_locks');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('invitation_pending'));
    }
};
