@extends('layouts.app')

@section('title', 'Job Listings')

@section('content')
    <div class="space-y-6">
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6">
            <form method="GET" action="{{ route('jobs.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        placeholder="Job title or description..."
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                    >
                </div>

                <div>
                    <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                    <select
                        name="company"
                        id="company"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                    >
                        <option value="">All Companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company }}" {{ request('company') === $company ? 'selected' : '' }}>
                                {{ $company }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                    <select
                        name="location"
                        id="location"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                    >
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location }}" {{ request('location') === $location ? 'selected' : '' }}>
                                {{ $location }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md"
                    >
                        Filter Jobs
                    </button>
                </div>
            </form>
        </div>

        <!-- Job Listings -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-semibold">Job Listings ({{ $jobs->total() }})</h2>
                <a
                    href="{{ route('jobs.dashboard') }}"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm"
                >
                    Back to Dashboard
                </a>
            </div>

            @if($jobs->count())
                <div class="divide-y divide-gray-200">
                    @foreach($jobs as $job)
                        <div class="p-6 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                                        <a href="{{ route('jobs.show', $job->id) }}" class="hover:text-blue-600">
                                            {{ $job->title }}
                                        </a>
                                    </h3>
                                    <div class="flex items-center space-x-4 text-sm text-gray-500 mb-2">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                {{ $job->company }}
                            </span>
                                        @if($job->location)
                                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ $job->location }}
                            </span>
                                        @endif
                                        <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $job->scraped_at->diffForHumans() }}
                            </span>
                                    </div>

                                    @if($job->description)
                                        <p class="text-gray-600 text-sm mb-3">
                                            {{ \Str::limit(strip_tags($job->description), 150) }}
                                        </p>
                                    @endif

                                    <div class="flex flex-wrap gap-2">
                                        @if($job->employment_type)
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                {{ $job->employment_type }}
                            </span>
                                        @endif
                                        @if($job->remote_type)
                                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                {{ $job->remote_type }}
                            </span>
                                        @endif
                                        @if($job->salary)
                                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                {{ $job->salary }}
                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="ml-4 flex flex-col space-y-2">
                                    <a
                                        href="{{ $job->url }}"
                                        target="_blank"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm inline-flex items-center"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        Apply
                                    </a>
                                    <button
                                        onclick="deleteJob({{ $job->id }})"
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $jobs->links() }}
                </div>
            @else
                <div class="p-6 text-center text-gray-500">
                    <p>No job listings found.</p>
                    <a href="{{ route('jobs.dashboard') }}" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                        Go to dashboard to start scraping
                    </a>
                </div>
            @endif
        </div>
    </div>

    <script>
        async function deleteJob(jobId) {
            if (!confirm('Are you sure you want to delete this job listing?')) {
                return;
            }

            try {
                const response = await fetch(`/jobs/delete/${jobId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Job listing deleted successfully');
                    window.location.reload();
                } else {
                    showToast('Failed to delete job listing', 'error');
                }
            } catch (error) {
                showToast('An error occurred while deleting', 'error');
                console.error('Delete error:', error);
            }
        }
    </script>
@endsection
