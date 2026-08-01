<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $q = JobPosting::query()
            ->with(['category:id,name', 'employer:id,company_name,logo'])
            ->where('status', 'approved')
            ->whereDate('deadline', '>=', now()->toDateString());

        if ($search = trim((string) $request->query('q', ''))) {
            $q->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('requirements', 'like', "%{$search}%")
                    ->orWhereHas('employer', fn ($e) => $e->where('company_name', 'like', "%{$search}%"));
            });
        }
        if ($loc = $request->query('location')) {
            $q->where('location', $loc);
        }
        if ($cat = (int) $request->query('category')) {
            $q->where('category_id', $cat);
        }
        if ($type = $request->query('job_type')) {
            $q->where('job_type', $type);
        }
        if (is_numeric($request->query('salary_min'))) {
            $q->where('salary_max', '>=', (float) $request->query('salary_min'));
        }

        $jobs = $q->latest()->get()->map(fn (JobPosting $j) => [
            'id' => $j->id,
            'title' => $j->title,
            'description' => $j->description,
            'location' => $j->location,
            'job_type' => $j->job_type,
            'salary_min' => $j->salary_min,
            'salary_max' => $j->salary_max,
            'deadline' => $j->deadline?->format('Y-m-d'),
            'created_at' => $j->created_at,
            'category_name' => $j->category?->name,
            'company_name' => $j->employer?->company_name,
            'logo' => $j->employer?->logo,
        ]);

        return response()->json([
            'ok' => true,
            'jobs' => $jobs,
            'filters' => [
                'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
                'locations' => JobPosting::query()->where('status', 'approved')->distinct()->orderBy('location')->pluck('location'),
                'job_types' => ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote'],
            ],
        ]);
    }

    public function show(Request $request, ?int $id = null)
    {
        $id = $id ?: (int) $request->query('id');
        if ($id <= 0) {
            return response()->json(['ok' => false, 'error' => 'Job id is required.'], 400);
        }

        if (!$request->attributes->get('auth_user')) {
            $parsed = \App\Services\ApiTokenService::parse($request->header('Authorization'));
            if ($parsed) {
                $authUser = User::query()->find($parsed['user_id']);
                if ($authUser && $authUser->isActive()) {
                    $request->attributes->set('auth_user', $authUser);
                }
            }
        }

        $job = JobPosting::query()->with(['category', 'employer'])->find($id);
        if (!$job) {
            return response()->json(['ok' => false, 'error' => 'Job not found.'], 404);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user');
        $canView = $job->status === 'approved'
            || ($user && in_array($user->role, ['admin', 'employer'], true));

        if (!$canView) {
            return response()->json(['ok' => false, 'error' => 'Job not available.'], 404);
        }

        $hasApplied = false;
        $isBookmarked = false;
        if ($user && $user->role === 'candidate' && $user->candidate) {
            $cid = $user->candidate->id;
            $hasApplied = Application::query()->where('job_id', $id)->where('candidate_id', $cid)->exists();
            $isBookmarked = Bookmark::query()->where('job_id', $id)->where('candidate_id', $cid)->exists();
        }

        return response()->json([
            'ok' => true,
            'job' => array_merge($job->toArray(), [
                'category_name' => $job->category?->name,
                'company_name' => $job->employer?->company_name,
                'logo' => $job->employer?->logo,
                'company_desc' => $job->employer?->description,
                'industry' => $job->employer?->industry,
                'website' => $job->employer?->website,
                'address' => $job->employer?->address,
            ]),
            'has_applied' => $hasApplied,
            'is_bookmarked' => $isBookmarked,
        ]);
    }

    public function apply(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user');
        $candidate = $user->candidate;
        if (!$candidate) {
            return response()->json(['ok' => false, 'error' => 'Candidate profile not found.'], 404);
        }
        if (!$candidate->resume) {
            return response()->json(['ok' => false, 'error' => 'Please upload your resume before applying.'], 400);
        }

        $jobId = (int) $request->input('job_id');
        $job = JobPosting::query()
            ->where('id', $jobId)
            ->where('status', 'approved')
            ->whereDate('deadline', '>=', now()->toDateString())
            ->first();

        if (!$job) {
            return response()->json(['ok' => false, 'error' => 'This job is not accepting applications.'], 400);
        }

        if (Application::query()->where('job_id', $jobId)->where('candidate_id', $candidate->id)->exists()) {
            return response()->json(['ok' => false, 'error' => 'You have already applied to this job.'], 400);
        }

        Application::query()->create([
            'job_id' => $jobId,
            'candidate_id' => $candidate->id,
            'cover_letter' => $request->input('cover_letter') ?: null,
            'status' => 'pending',
        ]);

        return response()->json(['ok' => true, 'message' => 'Application submitted successfully.']);
    }

    public function bookmark(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user');
        $candidate = $user->candidate;
        $jobId = (int) $request->input('job_id');

        $existing = Bookmark::query()->where('job_id', $jobId)->where('candidate_id', $candidate->id)->first();
        if ($existing) {
            $existing->delete();
            return response()->json(['ok' => true, 'bookmarked' => false, 'message' => 'Removed from saved jobs.']);
        }

        Bookmark::query()->create([
            'candidate_id' => $candidate->id,
            'job_id' => $jobId,
        ]);

        return response()->json(['ok' => true, 'bookmarked' => true, 'message' => 'Job saved.']);
    }
}
