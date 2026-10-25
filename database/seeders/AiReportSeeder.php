<?php

namespace Database\Seeders;

use App\Services\AdminAiService;
use App\Services\LearnerInterviewAnalyzer;
use Illuminate\Database\Seeder;

class AiReportSeeder extends Seeder
{
    public function run(): void
    {
        /** Explicit opt-in only: snapshot real data without contacting an unconfigured AI service. */
        if (! app(LearnerInterviewAnalyzer::class)->isConfigured()) {
            app(AdminAiService::class)->requestReport(now()->subDays(13), now());
        }
    }
}
