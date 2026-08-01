<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Employer;
use App\Models\JobPosting;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $featured = JobPosting::query()
            ->with(['category:id,name', 'employer:id,company_name,logo'])
            ->where('status', 'approved')
            ->whereDate('deadline', '>=', now()->toDateString())
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (JobPosting $j) => [
                'id' => $j->id,
                'title' => $j->title,
                'location' => $j->location,
                'job_type' => $j->job_type,
                'salary_min' => $j->salary_min,
                'salary_max' => $j->salary_max,
                'deadline' => $j->deadline?->format('Y-m-d'),
                'category_name' => $j->category?->name,
                'company_name' => $j->employer?->company_name,
                'logo' => $j->employer?->logo,
            ]);

        $categories = Category::query()
            ->withCount(['jobs as job_count' => function ($q) {
                $q->where('status', 'approved')->whereDate('deadline', '>=', now()->toDateString());
            }])
            ->orderBy('name')
            ->get();

        $locations = JobPosting::query()
            ->where('status', 'approved')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        return response()->json([
            'ok' => true,
            'stats' => [
                'jobs' => JobPosting::query()->where('status', 'approved')->whereDate('deadline', '>=', now()->toDateString())->count(),
                'companies' => Employer::query()->count(),
                'candidates' => Candidate::query()->count(),
            ],
            'featured' => $featured,
            'categories' => $categories,
            'locations' => $locations,
        ]);
    }
}
