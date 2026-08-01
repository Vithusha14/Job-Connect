<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Bookmark;
use App\Models\User;
use App\Support\UserPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CandidateController extends Controller
{
    public function dashboard(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user');
        $c = $user->candidate;
        $cid = $c->id;

        $recent = Application::query()
            ->with(['job.employer'])
            ->where('candidate_id', $cid)
            ->latest('applied_at')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'status' => $a->status,
                'applied_at' => $a->applied_at ?? $a->created_at,
                'job_id' => $a->job_id,
                'title' => $a->job?->title,
                'location' => $a->job?->location,
                'company_name' => $a->job?->employer?->company_name,
            ]);

        return response()->json([
            'ok' => true,
            'stats' => [
                'totalApps' => Application::query()->where('candidate_id', $cid)->count(),
                'pending' => Application::query()->where('candidate_id', $cid)->where('status', 'pending')->count(),
                'shortlisted' => Application::query()->where('candidate_id', $cid)->where('status', 'shortlisted')->count(),
                'selected' => Application::query()->where('candidate_id', $cid)->where('status', 'selected')->count(),
                'bookmarks' => Bookmark::query()->where('candidate_id', $cid)->count(),
            ],
            'recent' => $recent,
            'profile' => UserPayload::make($user)['profile'] ?? null,
        ]);
    }

    public function profile(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user');
        $c = $user->candidate;

        if ($request->isMethod('get')) {
            return response()->json([
                'ok' => true,
                'profile' => UserPayload::make($user)['profile'] ?? null,
                'email' => $user->email,
            ]);
        }

        $fullName = trim((string) $request->input('full_name', ''));
        if ($fullName === '') {
            return response()->json(['ok' => false, 'error' => 'Full name is required.'], 400);
        }

        $photo = $c->photo;
        $resume = $c->resume;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            if (!$file->isValid() || !str_starts_with((string) $file->getMimeType(), 'image/')) {
                return response()->json(['ok' => false, 'error' => 'Invalid photo file.'], 400);
            }
            $name = 'photo_' . $c->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(base_path('../uploads/photos'), $name);
            $photo = $name;
        }

        if ($request->hasFile('resume')) {
            $file = $request->file('resume');
            if (!$file->isValid() || $file->getMimeType() !== 'application/pdf') {
                return response()->json(['ok' => false, 'error' => 'Resume must be PDF.'], 400);
            }
            $name = 'resume_' . $c->id . '_' . time() . '.pdf';
            $file->move(base_path('../uploads/resumes'), $name);
            $resume = $name;
        }

        $c->update([
            'full_name' => $fullName,
            'phone' => $request->input('phone') ?: null,
            'location' => $request->input('location') ?: null,
            'skills' => $request->input('skills') ?: null,
            'education' => $request->input('education') ?: null,
            'experience' => $request->input('experience') ?: null,
            'bio' => $request->input('bio') ?: null,
            'photo' => $photo,
            'resume' => $resume,
        ]);

        $user->load('candidate');

        return response()->json([
            'ok' => true,
            'message' => 'Profile updated.',
            'profile' => UserPayload::make($user)['profile'],
        ]);
    }

    public function applications(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user');
        $q = Application::query()
            ->with(['job.employer'])
            ->where('candidate_id', $user->candidate->id);

        if (in_array($request->query('status'), ['pending', 'shortlisted', 'rejected', 'selected'], true)) {
            $q->where('status', $request->query('status'));
        }

        $apps = $q->latest('applied_at')->get()->map(fn ($a) => [
            'id' => $a->id,
            'job_id' => $a->job_id,
            'status' => $a->status,
            'applied_at' => $a->applied_at ?? $a->created_at,
            'title' => $a->job?->title,
            'location' => $a->job?->location,
            'job_type' => $a->job?->job_type,
            'company_name' => $a->job?->employer?->company_name,
        ]);

        return response()->json(['ok' => true, 'applications' => $apps]);
    }

    public function bookmarks(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user');
        $cid = $user->candidate->id;

        if ($request->isMethod('post') && $request->query('action') === 'remove') {
            Bookmark::query()
                ->where('candidate_id', $cid)
                ->where('job_id', (int) $request->input('job_id'))
                ->delete();

            return response()->json(['ok' => true, 'message' => 'Removed from saved jobs.']);
        }

        $items = Bookmark::query()
            ->with(['job.employer', 'job.category'])
            ->where('candidate_id', $cid)
            ->latest()
            ->get()
            ->map(function ($b) {
                $j = $b->job;
                return [
                    'id' => $j?->id,
                    'title' => $j?->title,
                    'location' => $j?->location,
                    'job_type' => $j?->job_type,
                    'salary_min' => $j?->salary_min,
                    'salary_max' => $j?->salary_max,
                    'company_name' => $j?->employer?->company_name,
                    'logo' => $j?->employer?->logo,
                    'category_name' => $j?->category?->name,
                    'saved_at' => $b->created_at,
                ];
            });

        return response()->json(['ok' => true, 'bookmarks' => $items]);
    }
}
