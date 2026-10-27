<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('queue {--once : Proses satu pekerjaan lalu keluar} {--stop-when-empty : Keluar setelah antrean kosong}')]
#[Description('Jalankan worker terpusat untuk analisis pelajar, laporan AI, draf kuis, dan audio.')]
class WorkApplicationQueue extends Command
{
    public function handle(): int
    {
        return $this->call('queue:work', [
            'connection' => 'learner-analysis',
            '--queue' => 'learner-analysis,default',
            '--sleep' => 2,
            '--tries' => 3,
            '--timeout' => 1830,
            '--max-time' => 3600,
            '--once' => $this->option('once'),
            '--stop-when-empty' => $this->option('stop-when-empty'),
        ]);
    }
}
