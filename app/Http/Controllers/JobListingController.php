<?php
namespace App\Http\Controllers;

use App\Models\JobListing;
use Illuminate\Http\Request;

class JobListingController extends Controller
{
    public function index(Request $request)
    {
        $query = JobListing::query();

        // Apply filters
        if ($request->filled('company')) {
            $query->where('company', $request->company);
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'scraped_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $jobs = $query->paginate(20)->withQueryString();

        // Get filter options
        $companies = JobListing::distinct('company')->pluck('company')->sort();
        $locations = JobListing::distinct('location')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->pluck('location')
            ->sort();

        return view('jobs.index', compact('jobs', 'companies', 'locations'));
    }

    public function show(JobListing $job)
    {
        return view('jobs.show', compact('job'));
    }

    public function destroy(JobListing $job)
    {
        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job listing deleted successfully'
        ]);
    }
}
