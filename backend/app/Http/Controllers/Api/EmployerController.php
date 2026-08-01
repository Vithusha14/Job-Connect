<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Category;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\Request;

class EmployerController extends Controller
{
    public function dashboard(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user');
        $e = $user->employer;
        $eid = $e->id;

        $jobs = JobPosting::query()
            ->where('employer_id', $eid)
            ->withCount('applications as applicant_count')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'ok' => true,
            'stats' => [
                'totalJobs' => JobPosting::query()->where('employer_id', $eid)->count(),
                'activeJobs' => JobPosting::query()->where('employer_id', $eid)->where('status', 'approved')->count(),
                'pendingJobs' => JobPosting::query()->where('employer_id', $eid)->where('status', 'pending_approval')->count(),
                'totalApplicants' => Application::query()->whereHas('job', fn ($q) => $q->where('employer_id', $eid))->count(),
            ],
            'jobs' => $jobs,
            'profile' => [
                'id' => $e->id,
                'company_name' => $e->company_name,
            ],
        ]);
    }

    public function jobs(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user');
        $e = $user->employer;

        if ($request->isMethod('get')) {
            $jobs = JobPosting::query()
                ->with('category:id,name')
                ->withCount('applications as applicant_count')
                ->where('employer_id', $e->id)
                ->latest()
                ->get()
                ->map(fn ($j) => array_merge($j->toArray(), [
                    'category_name' => $j->category?->name,
                ]));

            return response()->json([
                'ok' => true,
                'jobs' => $jobs,
                'categories' => Category::query()->orderBy('name')->get(),
            ]);
        }

        $action = $request->input('action');

        if ($action === 'create') {
            $title = trim((string) $request->input('title'));
            $categoryId = (int) $request->input('category_id');
            $location = trim((string) $request->input('location'));
            $description = trim((string) $request->input('description'));
            $requirements = trim((string) $request->input('requirements'));
            $deadline = $request->input('deadline');

            if ($title === '' || $categoryId <= 0 || $location === '' || $description === '' || $requirements === '' || !$deadline) {
                return response()->json(['ok' => false, 'error' => 'Please fill all required job fields.'], 400);
            }

            $job = JobPosting::query()->create([
                'employer_id' => $e->id,
                'category_id' => $categoryId,
                'title' => $title,
                'description' => $description,
                'requirements' => $requirements,
                'salary_min' => $request->filled('salary_min') ? (float) $request->input('salary_min') : null,
                'salary_max' => $request->filled('salary_max') ? (float) $request->input('salary_max') : null,
                'location' => $location,
                'job_type' => $request->input('job_type', 'Full-time'),
                'deadline' => $deadline,
                'status' => 'pending_approval',
                'vacancies' => max(1, (int) $request->input('vacancies', 1)),
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Job submitted for admin approval.',
                'id' => $job->id,
            ], 201);
        }

        $jobId = (int) $request->input('job_id');
        $job = JobPosting::query()->where('id', $jobId)->where('employer_id', $e->id)->first();
        if (!$job) {
            return response()->json(['ok' => false, 'error' => 'Job not found.'], 404);
        }

        if ($action === 'close') {
            $job->update(['status' => 'closed']);
        } elseif ($action === 'reopen') {
            $job->update(['status' => 'pending_approval']);
        } elseif ($action === 'delete') {
            $job->delete();
        } else {
            return response()->json(['ok' => false, 'error' => 'Invalid action.'], 400);
        }

        return response()->json(['ok' => true, 'message' => 'Job updated.']);
    }

    public function applicants(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user');
        $jobId = (int) $request->query('job_id');
        $job = JobPosting::query()->where('id', $jobId)->where('employer_id', $user->employer->id)->first();
        if (!$job) {
            return response()->json(['ok' => false, 'error' => 'Job not found.'], 404);
        }

        if ($request->isMethod('post')) {
            $status = $request->input('status');
            if (!in_array($status, ['pending', 'shortlisted', 'rejected', 'selected'], true)) {
                return response()->json(['ok' => false, 'error' => 'Invalid status.'], 400);
            }
            Application::query()
                ->where('id', (int) $request->input('application_id'))
                ->where('job_id', $jobId)
                ->update(['status' => $status]);

            return response()->json(['ok' => true, 'message' => 'Status updated.']);
        }

        $apps = Application::query()
            ->with(['candidate.user'])
            ->where('job_id', $jobId)
            ->latest('applied_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'status' => $a->status,
                'applied_at' => $a->applied_at ?? $a->created_at,
                'cover_letter' => $a->cover_letter,
                'full_name' => $a->candidate?->full_name,
                'phone' => $a->candidate?->phone,
                'skills' => $a->candidate?->skills,
                'resume' => $a->candidate?->resume,
                'photo' => $a->candidate?->photo,
                'email' => $a->candidate?->user?->email,
            ]);

        return response()->json(['ok' => true, 'job' => $job, 'applicants' => $apps]);
    }
}
