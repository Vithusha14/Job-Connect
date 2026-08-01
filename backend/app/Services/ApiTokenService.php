<?php

namespace App\Services;

use App\Models\User;

class ApiTokenService
{
    public static function create(User $user): string
    {
        $exp = time() + (60 * 60 * 24 * 7);
        $payload = $user->id . '|' . $user->role . '|' . $exp;
        $sig = hash_hmac('sha256', $payload, self::secret());

        return rtrim(strtr(base64_encode($payload . '|' . $sig), '+/', '-_'), '=');
    }

    public static function parse(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        $token = preg_replace('/^Bearer\s+/i', '', $token) ?? '';
        $decoded = base64_decode(strtr($token, '-_', '+/'), true);
        if (!$decoded) {
            return null;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 4) {
            return null;
        }

        [$userId, $role, $exp, $sig] = $parts;
        $payload = $userId . '|' . $role . '|' . $exp;
        $expected = hash_hmac('sha256', $payload, self::secret());

        if (!hash_equals($expected, $sig) || (int) $exp < time()) {
            return null;
        }

        return [
            'user_id' => (int) $userId,
            'role' => $role,
        ];
    }

    private static function secret(): string
    {
        return (string) config('app.api_secret', env('API_SECRET', 'jobconnect_api_secret_change_me'));
    }
}
