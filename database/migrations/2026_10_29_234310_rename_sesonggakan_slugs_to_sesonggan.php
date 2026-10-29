<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (['learning_paths', 'lessons'] as $table) {
                $rows = DB::table($table)->where('slug', 'sesonggakan')->orWhere('slug', 'like', 'sesonggakan-%')
                    ->select(['id', 'slug'])->lazyById(100);
                foreach ($rows as $row) {
                    $slug = 'sesonggan'.substr($row->slug, strlen('sesonggakan'));
                    if (DB::table($table)->where('slug', $slug)->exists()) {
                        throw new RuntimeException("Slug {$table}.{$slug} sudah dipakai. Tidak ada data yang digabung/dihapus; periksa duplikat sebelum migrasi ulang.");
                    }
                    DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
                }
            }
        });
    }

    public function down(): void
    {
        // Keep canonical URLs; old bookmarks are handled by the legacy redirect routes.
    }
};
