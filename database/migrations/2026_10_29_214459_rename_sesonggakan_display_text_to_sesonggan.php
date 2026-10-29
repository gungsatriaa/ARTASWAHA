<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $columnsByTable = [
                'learning_paths' => ['title', 'description'],
                'lessons' => ['title', 'summary', 'content', 'exercise_prompt', 'exercise_explanation'],
                'quizzes' => ['title', 'description'],
                'quiz_questions' => ['prompt', 'explanation'],
            ];
            foreach ($columnsByTable as $table => $columns) {
                foreach (DB::table($table)->select(['id', ...$columns])->lazyById(100) as $row) {
                    $changes = [];
                    foreach ($columns as $column) {
                        if (! is_string($row->{$column})) {
                            continue;
                        }
                        $text = strtr($row->{$column}, [
                            'Nama jalur Sesonggakan dipertahankan dari menu platform. ' => '',
                            'Sesonggakan' => 'Sesonggan', 'sesonggakan' => 'sesonggan', 'SESONGGAKAN' => 'SESONGGAN',
                        ]);
                        if ($text !== $row->{$column}) {
                            $changes[$column] = $text;
                        }
                    }
                    if ($changes !== []) {
                        DB::table($table)->where('id', $row->id)->update($changes);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Keep the correction: reversing it would also rename originally correct Sesonggan text.
    }
};
