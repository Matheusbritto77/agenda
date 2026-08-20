<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\TeamMember;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

class ResolvePublicBookingTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        URL::forceRootUrl($request->getSchemeAndHttpHost());

        $tenant = $this->resolveTenant($request);

        if (! $tenant) {
            if ($this->shouldAllowLandingPage($request)) {
                return $this->toSymfonyResponse($next($request), $request);
            }
            abort(404, 'Estabelecimento não encontrado.');
        }

        $request->attributes->set('bookingTenant', $tenant);
        app()->instance('bookingTenant', $tenant);

        return $this->toSymfonyResponse($next($request), $request);
    }

    private function toSymfonyResponse(mixed $response, Request $request): Response
    {
        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        if (! $response instanceof Response) {
            $response = response($response);
        }

        return $response;
    }

    private function resolveTenant(Request $request): ?User
    {
        $host = strtolower($request->getHost());
        $appUrlHost = strtolower((string) (parse_url((string) config('app.url', ''), PHP_URL_HOST) ?: ''));
        $baseDomain = $appUrlHost ?: strtolower((string) config('app.domain', 'localhost'));

        if ($this->isLocalHost($host)) {
            return $this->resolveLocalTenant($request, $host);
        }

        $customTenant = User::query()
            ->where('active_domain_type', 'custom')
            ->whereNotNull('custom_domain')
            ->get()
            ->first(function (User $user) use ($host): bool {
                return strtolower((string) $user->custom_domain) === $host;
            });

        if ($customTenant) {
            return $customTenant;
        }

        $legacyCustomMember = TeamMember::query()
            ->whereNotNull('custom_domain')
            ->get()
            ->first(function (TeamMember $member) use ($host): bool {
                return strtolower((string) $member->custom_domain) === $host;
            });

        if ($legacyCustomMember?->user) {
            $request->attributes->set('selectedProfessional', $legacyCustomMember);
            return $legacyCustomMember->user;
        }

        $subdomain = $this->extractSubdomain($host, $baseDomain);

        if ($subdomain !== null) {
            $subdomainTenant = User::query()
                ->where('active_domain_type', 'subdomain')
                ->where('subdomain', $subdomain)
                ->first();

            if ($subdomainTenant) {
                return $subdomainTenant;
            }

            $legacyMember = TeamMember::query()
                ->where('subdomain', $subdomain)
                ->first();

            if ($legacyMember?->user) {
                $request->attributes->set('selectedProfessional', $legacyMember);
                return $legacyMember->user;
            }

            return null;
        }

        if ($host === $baseDomain || $host === $appUrlHost) {
            return null;
        }

        return null;
    }

    private function resolveLocalTenant(Request $request, string $host): ?User
    {
        $localSubdomain = $this->extractLocalSubdomain($host);

        if ($localSubdomain !== null) {
            $localTenant = User::query()
                ->where(function ($query) use ($localSubdomain): void {
                    $query->where('subdomain', $localSubdomain)
                        ->orWhere('custom_domain', $localSubdomain);
                })
                ->first();

            if ($localTenant) {
                return $localTenant;
            }

            $legacyMember = TeamMember::query()
                ->where(function ($query) use ($localSubdomain): void {
                    $query->where('subdomain', $localSubdomain)
                        ->orWhere('custom_domain', $localSubdomain);
                })
                ->first();

            if ($legacyMember?->user) {
                $request->attributes->set('selectedProfessional', $legacyMember);
                return $legacyMember->user;
            }

            return null;
        }

        return null;
    }

    private function isLocalHost(string $host): bool
    {
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        return preg_match('/\.(localhost|local|test|internal)$/', $host) === 1;
    }

    private function shouldAllowLandingPage(Request $request): bool
    {
        return $request->isMethod('get') && $request->path() === '/';
    }

    private function extractLocalSubdomain(string $host): ?string
    {
        if (! preg_match('/^(?<subdomain>[a-z0-9-]+)\.(localhost|local|test|internal)$/', $host, $matches)) {
            return null;
        }

        $subdomain = strtolower((string) ($matches['subdomain'] ?? ''));

        return $subdomain === '' ? null : $subdomain;
    }

    private function extractSubdomain(string $host, string $baseDomain): ?string
    {
        if ($host === '' || $baseDomain === '') {
            return null;
        }

        if ($host === $baseDomain) {
            return null;
        }

        $suffix = '.' . $baseDomain;

        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $subdomain = substr($host, 0, -strlen($suffix));

        if ($subdomain === '' || str_contains($subdomain, '.')) {
            return null;
        }

        return $subdomain;
    }
}
