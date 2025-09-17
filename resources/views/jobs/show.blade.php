@extends('layouts.app')

@section('title', $job->title . ' - ' . $job->company)

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">{{ $job->title }}</h1>
                        <div class="flex items-center space-x-6 text-blue-100">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            {{ $job->company }}
                        </span>
                            @if($job->location)
                                <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            </svg>
                            {{ $job->location }}
                        </span>
                            @endif
                            <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Scraped {{ $job->scraped_at->diffForHumans() }}
                        </span>
                        </div>
                    </div>
                    <a
                        href="{{ $job->url }}"
                        target="_blank"
                        class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Apply Now
                    </a>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Job Details -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    @if($job->employment_type)
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <h3 class="font-semibold text-gray-900">Employment Type</h3>
                            <p class="text-gray-600">{{ $job->employment_type }}</p>
                        </div>
                    @endif

                    @if($job->remote_type)
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <h3 class="font-semibold text-gray-900">Work Type</h3>
                            <p class="text-gray-600">{{ $job->remote_type }}</p>
                        </div>
                    @endif

                    @if($job->salary)
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <h3 class="font-semibold text-gray-900">Salary</h3>
                            <p class="text-gray-600">{{ $job->salary }}</p>
                        </div>
                    @endif
                </div>

                <!-- Description -->
                @if($job->description)
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">Job Description</h2>
                        <div class="prose max-w-none text-gray-700">
                            {!! nl2br(e($job->description)) !!}
                        </div>
                    </div>
                @endif

                <!-- Requirements -->
                @if($job->requirements)
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">Requirements</h2>
                        <div class="prose max-w-none text-gray-700">
                            {!! nl2br(e($job->requirements)) !!}
                        </div>
                    </div>
                @endif

                <!-- Additional Details -->
                @if($job->details && count($job->details))
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">Additional Information</h2>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <pre class="text-sm text-gray-600 whitespace-pre-wrap">{{ json_encode($job->details, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <a
                        href="{{ route('jobs.index') }}"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded"
                    >
                        ← Back to Jobs
                    </a>

                    <div class="flex space-x-2">
                        <a
                            href="{{ $job->url }}"
                            target="_blank"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded"
                        >
                            View Original Posting
                        </a>

                        <button
                            onclick="deleteJob({{ $job->id }})"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
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
                    setTimeout(() => {
                        window.location.href = '{{ route("jobs.index") }}';
                    }, 1000);
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
