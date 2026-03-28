<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;
use OpenRiC\Auth\Models\User;

class OpenRiCUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return User::where('active', true)->find($identifier);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return User::where('active', true)
            ->where('id', $identifier)
            ->where('remember_token', $token)
            ->first();
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->setRememberToken($token);
        $user->save();
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $emailOrUsername = $credentials['email'] ?? $credentials['username'] ?? null;
        if ($emailOrUsername === null) {
            return null;
        }

        $user = User::where('active', true)->where('email', $emailOrUsername)->first();
        if ($user === null) {
            $user = User::where('active', true)->where('username', $emailOrUsername)->first();
        }

        return $user;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return Hash::check($credentials['password'] ?? '', $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        if (Hash::needsRehash($user->getAuthPassword()) || $force) {
            $user->forceFill(['password' => Hash::make($credentials['password'])])->save();
        }
    }
}
