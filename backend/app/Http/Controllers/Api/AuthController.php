<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Employer;
use App\Models\User;
use App\Services\ApiTokenService;
use App\Support\UserPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->all();
        $v = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'error' => $v->errors()->first()], 400);
        }

        $user = User::query()->where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['ok' => false, 'error' => 'Invalid email or password.'], 401);
        }
        if (!$user->isActive()) {
            return response()->json(['ok' => false, 'error' => 'Your account has been blocked.'], 403);
        }

        return response()->json([
            'ok' => true,
            'token' => ApiTokenService::create($user),
            'user' => UserPayload::make($user),
            'message' => 'Login successful',
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->all();
        $role = ($data['role'] ?? 'candidate') === 'employer' ? 'employer' : 'candidate';

        $rules = [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
        ];
        if ($role === 'candidate') {
            $rules['full_name'] = 'required|string|max:120';
        } else {
            $rules['company_name'] = 'required|string|max:150';
        }

        $v = Validator::make($data, $rules);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'error' => $v->errors()->first()], 400);
        }

        $user = DB::transaction(function () use ($data, $role) {
            $user = User::query()->create([
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $role,
                'status' => 'active',
            ]);

            if ($role === 'candidate') {
                Candidate::query()->create([
                    'user_id' => $user->id,
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'] ?? null,
                ]);
            } else {
                Employer::query()->create([
                    'user_id' => $user->id,
                    'company_name' => $data['company_name'],
                    'industry' => $data['industry'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ]);
            }

            return $user->fresh(['candidate', 'employer']);
        });

        return response()->json([
            'ok' => true,
            'token' => ApiTokenService::create($user),
            'user' => UserPayload::make($user),
            'message' => 'Account created successfully',
        ], 201);
    }

    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user');
        $user->load(['candidate', 'employer']);

        return response()->json([
            'ok' => true,
            'user' => UserPayload::make($user),
        ]);
    }
}
