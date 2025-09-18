@extends('layouts.app')

@section('title', 'Job Scraper Dashboard')

@section('content')
    <div x-data="dashboard()" x-init="init()">
        <!-- Header Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Jobs</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalJobs) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.698"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">New Today</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($recentJobs) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Companies</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ count($companies) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Last Scrape</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            @if($lastScrapeLog)
                                {{ $lastScrapeLog->created_at->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="flex flex-wrap gap-4">
                <button
                    @click="scrapeAll()"
                    :disabled="isLoading"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                >
                    <svg x-show="isLoading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Scrape All Companies
                </button>

                <div class="flex gap-2">
                    @foreach($companies as $company)
                        <button
                            @click="scrapeCompany('{{ $company }}')"
                            :disabled="isLoading"
                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm disabled:opacity-50"
                        >
                            {{ $company }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Company Statistics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Jobs by Company Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Jobs by Company</h3>
                <canvas id="jobsByCompanyChart" width="400" height="200"></canvas>
            </div>

            <!-- Scraping Performance -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Scraping Performance (Last 7 days)</h3>
                <div class="space-y-4">
                    @foreach($companyStats as $company => $stats)
                        <div class="flex justify-between items-center">
                            <span class="font-medium">{{ $company }}</span>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">{{ $stats['successful_runs'] }}/{{ $stats['total_runs'] }}</span>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div
                                        class="bg-green-500 h-2 rounded-full"
                                        style="width: {{ $stats['total_runs'] > 0 ? ($stats['successful_runs'] / $stats['total_runs']) * 100 : 0 }}%"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Recent Scraping Activity</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jobs Found</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" x-ref="logsTable">
                    @foreach($recentLogs as $log)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->created_at->format('M j, H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $log->company }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->status === 'success')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Success</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->jobs_found ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->duration_seconds ? $log->duration_seconds . 's' : 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function dashboard() {
            return {
                isLoading: false,

                init() {
                    this.destroyCharts();
                    this.initCharts();
                    // Auto-refresh logs every 30 seconds
                    setInterval(() => this.refreshLogs(), 30000);
                },

                async scrapeAll() {
                    this.isLoading = true;

                    try {
                        const response = await fetch('{{ route("jobs.scrape") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            showToast(`Successfully scraped ${data.jobs_count} jobs from all companies`);
                            // Refresh page after 2 seconds
                            setTimeout(() => window.location.reload(), 2000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (error) {
                        showToast('An error occurred while scraping', 'error');
                        console.error('Scraping error:', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async scrapeCompany(company) {
                    this.isLoading = true;

                    try {
                        const response = await fetch(`/jobs/scrape/${company}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            showToast(`Successfully scraped ${data.jobs_count} jobs from ${company}`);
                            setTimeout(() => window.location.reload(), 2000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (error) {
                        showToast(`An error occurred while scraping ${company}`, 'error');
                        console.error('Scraping error:', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async refreshLogs() {
                    try {
                        const response = await fetch('{{ route("jobs.api.logs") }}');
                        const logs = await response.json();

                        // Update the table (simplified version)
                        // In a real app, you'd want to update the DOM more efficiently
                        console.log('Logs refreshed:', logs.length);
                    } catch (error) {
                        console.error('Error refreshing logs:', error);
                    }
                },

                initCharts() {
                    if (window.jobsByCompanyChartInstance) {
                        window.jobsByCompanyChartInstance.destroy();
                    }
                    // Jobs by Company Chart
                    const ctx = document.getElementById('jobsByCompanyChart').getContext('2d');
                    window.jobsByCompanyChartInstance = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: {!! json_encode($jobsByCompany->keys()) !!},
                            datasets: [{
                                data: {!! json_encode($jobsByCompany->pluck('count')) !!},
                                backgroundColor: [
                                    '#3B82F6',
                                    '#10B981',
                                    '#F59E0B',
                                    '#EF4444',
                                    '#8B5CF6',
                                    '#EC4899'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                },
                destroyCharts() {
                    if (window.jobsByCompanyChartInstance) {
                        window.jobsByCompanyChartInstance.destroy();
                        window.jobsByCompanyChartInstance = null;
                    }
                },
            }
        }
    </script>
@endsection
