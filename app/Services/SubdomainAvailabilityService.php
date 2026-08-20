<?php

namespace App\Services;

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Str;

class SubdomainAvailabilityService
{
    public function normalize(?string $subdomain): string
    {
        return Str::slug(strtolower(trim((string) $subdomain)), '-');
    }

    public function isAvailable(string $subdomain, ?int $ignoreUserId = null): bool
    {
        $subdomain = $this->normalize($subdomain);

        if ($subdomain === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $subdomain)) {
            return false;
        }

        $userQuery = User::query()->whereRaw('LOWER(subdomain) = ?', [$subdomain]);

        if ($ignoreUserId !== null) {
            $userQuery->whereKeyNot($ignoreUserId);
        }

        if ($userQuery->exists()) {
            return false;
        }

        return ! TeamMember::query()->whereRaw('LOWER(subdomain) = ?', [$subdomain])->exists();
    }

    public function suggest(string $subdomain, ?int $ignoreUserId = null): string
    {
        $base = $this->normalize($subdomain);

        if ($base === '') {
            $base = 'empresa';
        }

        $base = Str::limit($base, 50, '');
        $candidate = $base;
        $suffix = 2;

        while (! $this->isAvailable($candidate, $ignoreUserId)) {
            $candidateBase = Str::limit($base, max(1, 63 - strlen((string) $suffix) - 1), '');
            $candidate = $candidateBase . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
