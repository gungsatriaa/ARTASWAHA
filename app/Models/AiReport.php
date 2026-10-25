<?php

namespace App\Models;

use Database\Factories\AiReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiReport extends Model
{
    /** @use HasFactory<AiReportFactory> */
    use HasFactory;

    protected $fillable = ['period_start', 'period_end', 'input_hash', 'snapshot', 'status', 'result', 'completed_at'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'snapshot' => 'array', 'result' => 'array', 'completed_at' => 'datetime'];
    }
}
