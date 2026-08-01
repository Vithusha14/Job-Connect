<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Employer;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $appStats = Application::query()
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $catStats = Category::query()
            ->withCount('jobs as cnt')
            ->orderByDesc('cnt')
            ->limit(6)
            ->get(['id', 'name']);

        return response()->json([
            'ok' => true,
            'stats' => [
                'totalUsers' => User::query()->where('role', '!=', 'admin')->count(),
                'totalCandidates' => Candidate::query()->count(),
                'totalEmployers' => Employer::query()->count(),
                'totalJobs' => JobPosting::query()->count(),
                'approvedJobs' => JobPosting::query()->where('status', 'approved')->count(),
                'pendingJobs' => JobPosting::query()->where('status', 'pending_approval')->count(),
                'totalApps' => Application::query()->count(),
                'pendingApps' => Application::query()->where('status', 'pending')->count(),
            ],
            'app_stats' => $appStats,
            'category_stats' => $catStats->map(fn ($c) => ['name' => $c->name, 'cnt' => $c->cnt]),
        ]);
    }

    public function users(Request $request)
    {
        if ($request->isMethod('post')) {
            $userId = (int) $request->input('user_id');
            $action = $request->input('action');
            $target = User::query()->where('id', $userId)->where('role', '!=', 'admin')->first();
            if (!$target) {
                return response()->json(['ok' => false, 'error' => 'User not found.'], 404);
            }
            if ($action === 'block') {
                $target->update(['status' => 'blocked']);
            } elseif ($action === 'unblock') {
                $target->update(['status' => 'active']);
            } elseif ($action === 'delete') {
                $target->delete();
            } else {
                return response()->json(['ok' => false, 'error' => 'Invalid action.'], 400);
            }

            return response()->json(['ok' => true, 'message' => 'User updated.']);
        }

        $candidates = User::query()
            ->where('role', 'candidate')
            ->with('candidate')
            ->latest()
            ->get()
            ->map(fn ($u) => [
                'user_id' => $u->id,
                'email' => $u->email,
                'status' => $u->status,
                'created_at' => $u->created_at,
                'full_name' => $u->candidate?->full_name,
                'phone' => $u->candidate?->phone,
                'location' => $u->candidate?->location,
            ]);

        $employers = User::query()
            ->where('role', 'employer')
            ->with('employer')
            ->latest()
            ->get()
            ->map(fn ($u) => [
                'user_id' => $u->id,
                'email' => $u->email,
                'status' => $u->status,
                'created_at' => $u->created_at,
                'company_name' => $u->employer?->company_name,
                'industry' => $u->employer?->industry,
            ]);

        return response()->json([
            'ok' => true,
            'candidates' => $candidates,
            'employers' => $employers,
        ]);
    }

    public function jobs(Request $request)
    {
        if ($request->isMethod('post')) {
            $jobId = (int) $request->input('job_id');
            $action = $request->input('action');
            $job = JobPosting::query()->find($jobId);
            if (!$job) {
                return response()->json(['ok' => false, 'error' => 'Job not found.'], 404);
            }

            if ($action === 'approve') {
                $job->update(['status' => 'approved']);
            } elseif ($action === 'reject') {
                $job->update(['status' => 'rejected']);
            } elseif ($action === 'close') {
                $job->update(['status' => 'closed']);
            } elseif ($action === 'delete') {
                $job->delete();
            } else {
                return response()->json(['ok' => false, 'error' => 'Invalid action.'], 400);
            }

            return response()->json(['ok' => true, 'message' => 'Job updated.']);
        }

        $q = JobPosting::query()->with(['employer:id,company_name', 'category:id,name'])->withCount('applications as applicant_count');
        if (in_array($request->query('status'), ['pending_approval', 'approved', 'rejected', 'closed'], true)) {
            $q->where('status', $request->query('status'));
        }

        $jobs = $q->latest()->get()->map(fn ($j) => array_merge($j->toArray(), [
            'company_name' => $j->employer?->company_name,
            'category_name' => $j->category?->name,
        ]));

        return response()->json(['ok' => true, 'jobs' => $jobs]);
    }
}
