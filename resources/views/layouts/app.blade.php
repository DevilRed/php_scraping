<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Job Scraper Dashboard')</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js for statistics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Alpine.js for interactivity -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">
<nav class="bg-blue-600 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <h1 class="text-xl font-bold">Job Scraper</h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('jobs.dashboard') }}" class="hover:bg-blue-700 px-3 py-2 rounded">Dashboard</a>
                <a href="{{ route('jobs.index') }}" class="hover:bg-blue-700 px-3 py-2 rounded">Jobs</a>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto py-6 px-4">
    @yield('content')
</main>

<!-- Toast notifications -->
<div id="toast-container" class="fixed top-4 right-4 z-50"></div>

<script>
    // CSRF token setup for AJAX
    window.Laravel = {
        csrfToken: '{{ csrf_token() }}'
    };

    // Toast notification function
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `mb-2 px-4 py-2 rounded shadow-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
        toast.textContent = message;

        document.getElementById('toast-container').appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    // Setup AXIOS defaults
    if (typeof axios !== 'undefined') {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }
</script>

@stack('scripts')
</body>
</html>
