<?php

namespace App\Support;

use App\Models\User;

class UserPayload
{
    public static function make(User $user): array
    {
        $payload = [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'display_name' => 'User',
        ];

        if ($user->role === 'candidate') {
            $c = $user->candidate;
            if ($c) {
                $payload['display_name'] = $c->full_name;
                $payload['candidate_id'] = $c->id;
                $payload['profile'] = [
                    'id' => $c->id,
                    'full_name' => $c->full_name,
                    'phone' => $c->phone,
                    'photo' => $c->photo,
                    'skills' => $c->skills,
                    'education' => $c->education,
                    'experience' => $c->experience,
                    'resume' => $c->resume,
                    'bio' => $c->bio,
                    'location' => $c->location,
                ];
            }
        } elseif ($user->role === 'employer') {
            $e = $user->employer;
            if ($e) {
                $payload['display_name'] = $e->company_name;
                $payload['employer_id'] = $e->id;
                $payload['profile'] = [
                    'id' => $e->id,
                    'company_name' => $e->company_name,
                    'logo' => $e->logo,
                    'description' => $e->description,
                    'industry' => $e->industry,
                    'website' => $e->website,
                    'phone' => $e->phone,
                    'address' => $e->address,
                ];
            }
        } else {
            $payload['display_name'] = 'Administrator';
        }

        return $payload;
    }
}
