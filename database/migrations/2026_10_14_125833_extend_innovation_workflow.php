<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('ideas', function (Blueprint $table) {
            $table->json('publication')->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at')->nullable();
        });
        Schema::table('idea_results', function (Blueprint $table) {
            $table->json('measurements')->nullable();
            $table->text('limitations')->nullable();
            $table->timestamp('validated_at')->nullable();
        });
        Schema::table('voting_cycles', function (Blueprint $table) {
            $table->json('frozen_configuration')->nullable();
            $table->timestamp('closed_at')->nullable();
        });
        Schema::create('idea_voting_cycle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('ideas')->restrictOnDelete();
            $table->foreignId('voting_cycle_id')->constrained('voting_cycles')->restrictOnDelete();
            $table->json('snapshot');
            $table->json('outcome')->nullable();
            $table->unique(['idea_id', 'voting_cycle_id']);
        });
        Schema::create('idea_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('ideas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->timestamps();
            $table->unique(['idea_id', 'user_id']);
        });
        Schema::table('bonus_awards', function (Blueprint $table) {
            $table->foreignId('voting_cycle_id')->nullable()->constrained('voting_cycles')->restrictOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->unique(['idea_id', 'voting_cycle_id']);
        });
        foreach (['view_author_identity', 'view_audit_logs'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            Role::where('name', 'admin')->first()?->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        Schema::table('bonus_awards', function (Blueprint $table) {
            $table->dropUnique(['idea_id', 'voting_cycle_id']);
            $table->dropConstrainedForeignId('voting_cycle_id');
            $table->dropColumn('published_at');
        });
        Schema::dropIfExists('idea_conflicts');
        Schema::dropIfExists('idea_voting_cycle');
        Schema::table('voting_cycles', fn (Blueprint $table) => $table->dropColumn(['frozen_configuration', 'closed_at']));
        Schema::table('idea_results', fn (Blueprint $table) => $table->dropColumn(['measurements', 'limitations', 'validated_at']));
        Schema::table('ideas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by_user_id');
            $table->dropColumn(['publication', 'published_at']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
