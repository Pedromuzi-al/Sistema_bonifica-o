<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! collect(Schema::getForeignKeys('users'))->contains(fn ($key) => $key['columns'] === ['sector_id'])) {
            Schema::table('users', fn (Blueprint $table) => $table->foreign('sector_id')->references('id')->on('sectors')->nullOnDelete());
        }
        $keys = Schema::getForeignKeys('idea_beneficiary_sectors');
        if (! collect($keys)->contains(fn ($key) => $key['columns'] === ['idea_id'])) {
            Schema::table('idea_beneficiary_sectors', fn (Blueprint $table) => $table->foreign('idea_id')->references('id')->on('ideas')->cascadeOnDelete());
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropForeign(['sector_id']));
        Schema::table('idea_beneficiary_sectors', fn (Blueprint $table) => $table->dropForeign(['idea_id']));
    }
};
