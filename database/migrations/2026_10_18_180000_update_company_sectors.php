<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $catalog = [
                'Material Didático' => 'Produção e gestão de materiais didáticos.',
                'Sucesso do Cliente' => 'Relacionamento e acompanhamento do sucesso dos clientes.',
                'Tecnologia' => 'Desenvolvimento, infraestrutura e sistemas.',
                'Produto' => 'Gestão e evolução dos produtos.',
                'Administração' => 'Gestão administrativa, financeira e de pessoas.',
            ];
            foreach (['Operações' => 'Produto', 'Financeiro' => 'Administração', 'Vendas' => 'Sucesso do Cliente'] as $oldName => $newName) {
                $old = DB::table('sectors')->where('name', $oldName)->first();
                $target = DB::table('sectors')->where('name', $newName)->first();
                if (! $old) {
                    continue;
                }
                if (! $target) {
                    // Renaming in place retains every foreign key and historical record.
                    DB::table('sectors')->where('id', $old->id)->update(['name' => $newName, 'description' => $catalog[$newName], 'is_active' => true, 'updated_at' => now()]);

                    continue;
                }
                foreach (['users' => 'sector_id', 'teams' => 'sector_id', 'ideas' => 'origin_sector_id'] as $table => $column) {
                    DB::table($table)->where($column, $old->id)->update([$column => $target->id]);
                }
                foreach (DB::table('idea_beneficiary_sectors')->where('sector_id', $old->id)->orderBy('id')->get() as $link) {
                    DB::table('idea_beneficiary_sectors')->insertOrIgnore(['idea_id' => $link->idea_id, 'sector_id' => $target->id, 'created_at' => $link->created_at, 'updated_at' => $link->updated_at]);
                }
                DB::table('idea_beneficiary_sectors')->where('sector_id', $old->id)->delete();
                DB::table('sectors')->where('id', $old->id)->update(['is_active' => false, 'updated_at' => now()]);
            }
            DB::table('sectors')->whereNotIn('name', array_keys($catalog))->update(['is_active' => false, 'updated_at' => now()]);
            foreach ($catalog as $name => $description) {
                $existing = DB::table('sectors')->where('name', $name)->first();
                if ($existing) {
                    DB::table('sectors')->where('id', $existing->id)->update(['is_active' => true, 'updated_at' => now()]);
                } else {
                    DB::table('sectors')->insert(['name' => $name, 'description' => $description, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }, 3);
    }

    public function down(): void
    {
        // Data consolidation cannot be reversed without inventing which old sector
        // each record belonged to. Preserve the catalog and its references on rollback.
    }
};
