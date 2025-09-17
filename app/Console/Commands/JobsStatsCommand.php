<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobListing;
use App\Models\ScrapingLog;
use Carbon\Carbon;

class JobsStatsCommand extends Command
{
    protected $signature = 'jobs:stats
                          {--company= : Show stats for specific company}
                          {--days=7 : Number of days to include in stats}';

    protected $description = 'Display job scraping statistics and database status';

    public function handle(): int
    {
        $company = $this->option('company');
        $days = (int) $this->option('days');

        $this->info("Job Scraping Statistics - Last {$days} days");
        $this->line('');

        // Database overview
        $this->showDatabaseOverview($company);
        $this->line('');

        // Scraping logs overview
        $this->showScrapingLogsOverview($company, $days);
        $this->line('');

        // Recent activity
        $this->showRecentActivity($company, $days);

        return Command::SUCCESS;
    }

    private function showDatabaseOverview(?string $company): void
    {
        $query = JobListing::query();

        if ($company) {
            $query->where('company', $company);
        }

        $totalJobs = $query->count();
        $companiesCount = $query->distinct('company')->count();
        $recentJobs = $query->where('created_at', '>=', Carbon::now()->subDays(1))->count();

        $this->info('Database Overview:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Job Listings', number_format($totalJobs)],
                ['Companies', $companiesCount],
                ['Added Today', number_format($recentJobs)]
            ]
        );

        // Top companies
        if (!$company) {
            $topCompanies = JobListing::selectRaw('company, COUNT(*) as count')
                ->groupBy('company')
                ->orderByDesc('count')
                ->limit(5)
                ->get();

            $this->line('');
            $this->info('Jobs by Company:');
            $this->table(
                ['Company', 'Job Count'],
                $topCompanies->map(fn($item) => [$item->company, number_format($item->count)])->toArray()
            );
        }
    }

    private function showScrapingLogsOverview(?string $company, int $days): void
    {
        $query = ScrapingLog::where('created_at', '>=', Carbon::now()->subDays($days));

        if ($company) {
            $query->where('company', $company);
        }

        $logs = $query->get();

        $this->info('Scraping Performance:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Scraping Runs', $logs->count()],
                ['Successful Runs', $logs->where('status', 'success')->count()],
                ['Failed Runs', $logs->where('status', 'failure')->count()],
                ['Success Rate', $logs->count() > 0 ? round(($logs->where('status', 'success')->count() / $logs->count()) * 100, 1) . '%' : 'N/A'],
                ['Jobs Found', number_format($logs->sum('jobs_found'))],
                ['Jobs Saved', number_format($logs->sum('jobs_saved'))],
                ['Avg Duration', $logs->where('duration_seconds')->count() > 0 ? round($logs->avg('duration_seconds'), 1) . 's' : 'N/A']
            ]
        );
    }

    private function showRecentActivity(?string $company, int $days): void
    {
        $query = ScrapingLog::where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderByDesc('created_at')
            ->limit(10);

        if ($company) {
            $query->where('company', $company);
        }

        $recentLogs = $query->get();

        if ($recentLogs->isEmpty()) {
            $this->warn('No recent scraping activity found.');
            return;
        }

        $this->info('Recent Scraping Activity:');
        $this->table(
            ['Time', 'Company', 'Status', 'Jobs Found', 'Duration'],
            $recentLogs->map(function ($log) {
                return [
                    $log->created_at->format('M j, H:i'),
                    $log->company,
                    $log->status === 'success' ? '✓' : '✗',
                    $log->jobs_found ?? 'N/A',
                    $log->duration_seconds ? $log->duration_seconds . 's' : 'N/A'
                ];
            })->toArray()
        );
    }
}
