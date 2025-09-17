<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\JobScrapingService;
use App\Models\JobListing;
use App\Models\ScrapingLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class JobScrapingController extends Controller
{
    private JobScrapingService $scrapingService;

    public function __construct(JobScrapingService $scrapingService)
    {
        $this->scrapingService = $scrapingService;
    }

    public function dashboard()
    {
        $companies = $this->scrapingService->getAvailableCompanies();
        $totalJobs = JobListing::count();
        $recentJobs = JobListing::where('created_at', '>=', now()->subDay())->count();
        $lastScrapeLog = ScrapingLog::latest()->first();

        // Get job counts by company
        $jobsByCompany = JobListing::selectRaw('company, COUNT(*) as count')
            ->groupBy('company')
            ->orderByDesc('count')
            ->get()
            ->keyBy('company');

        // Get recent scraping activity
        $recentLogs = ScrapingLog::with([])
            ->latest()
            ->limit(10)
            ->get();

        // Get stats for each company
        $companyStats = [];
        foreach ($companies as $company) {
            $companyStats[$company] = $this->scrapingService->getScrapingStats($company, 7);
        }

        return view('jobs.dashboard', compact(
            'companies',
            'totalJobs',
            'recentJobs',
            'lastScrapeLog',
            'jobsByCompany',
            'recentLogs',
            'companyStats'
        ));
    }

    public function scrape(Request $request)
    {
        try {
            $jobs = $this->scrapingService->scrapeAll();

            return response()->json([
                'success' => true,
                'message' => 'Successfully scraped all companies',
                'jobs_count' => $jobs->count(),
                'redirect' => route('jobs.dashboard')
            ]);

        } catch (\Exception $e) {
            Log::error('Scraping failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Scraping failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function scrapeCompany(Request $request, string $company)
    {
        $request->validate([
            'filters' => 'sometimes|array',
            'filters.*' => 'string'
        ]);

        try {
            $filters = $request->get('filters', []);
            $jobs = $this->scrapingService->scrapeCompany($company, $filters);

            return response()->json([
                'success' => true,
                'message' => "Successfully scraped {$company}",
                'jobs_count' => $jobs->count(),
                'jobs' => $jobs->map(fn($job) => $job->toArray())->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error("Scraping {$company} failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => "Scraping {$company} failed: " . $e->getMessage()
            ], 500);
        }
    }

    public function stats(): JsonResponse
    {
        $companies = $this->scrapingService->getAvailableCompanies();
        $stats = [];

        foreach ($companies as $company) {
            $stats[$company] = $this->scrapingService->getScrapingStats($company, 7);
        }

        return response()->json($stats);
    }

    public function logs(Request $request): JsonResponse
    {
        $query = ScrapingLog::query();

        if ($request->has('company') && $request->company !== 'all') {
            $query->where('company', $request->company);
        }

        $logs = $query->latest()
            ->limit($request->get('limit', 50))
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'company' => $log->company,
                    'status' => $log->status,
                    'jobs_found' => $log->jobs_found,
                    'jobs_saved' => $log->jobs_saved,
                    'duration' => $log->duration_seconds,
                    'started_at' => $log->started_at->format('M j, Y H:i:s'),
                    'error_message' => $log->error_message,
                    'filters_applied' => $log->filters_applied
                ];
            });

        return response()->json($logs);
    }

    public function filters(string $company): JsonResponse
    {
        try {
            $filters = $this->scrapingService->getCompanyFilters($company);
            return response()->json($filters);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
