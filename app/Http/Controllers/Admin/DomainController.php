<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class DomainController extends Controller
{
    public function index(Request $request): JsonResponse|InertiaResponse
    {
        $domainSettings = $this->currentDomainSettings($request->user());

        if ($request->expectsJson()) {
            return response()->json($domainSettings);
        }

        return Inertia::render('Admin/Domain/Index', compact('domainSettings'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $request->merge([
                'subdomain' => $this->normalizeSubdomain($request->input('subdomain')),
                'custom_domain' => $this->normalizeCustomDomain($request->input('custom_domain')),
                'active_domain_type' => strtolower(trim((string) $request->input('active_domain_type', $request->user()?->active_domain_type ?? 'subdomain'))),
            ]);

            $user = $request->user();

            $validated = $request->validate([
                'subdomain' => [
                    'required',
                    'string',
                    'max:63',
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    Rule::unique(User::class, 'subdomain')->ignore($user?->id),
                ],
                'custom_domain' => [
                    'nullable',
                    'string',
                    'max:255',
                    'regex:/^(localhost|(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,})$/i',
                    Rule::unique(User::class, 'custom_domain')->ignore($user?->id),
                ],
                'active_domain_type' => ['required', Rule::in(['subdomain', 'custom'])],
            ]);

            if ($validated['active_domain_type'] === 'custom' && empty($validated['custom_domain'])) {
                throw ValidationException::withMessages([
                    'custom_domain' => 'Informe um domínio personalizado válido para ativar este tipo.',
                ]);
            }

            $user->update([
                'subdomain' => $validated['subdomain'],
                'custom_domain' => $validated['custom_domain'] ?? null,
                'active_domain_type' => $validated['active_domain_type'],
            ]);

            $domainSettings = $this->currentDomainSettings($user->fresh());

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Configurações de domínio salvas com sucesso.',
                    ...$domainSettings,
                ]);
            }

            return redirect()
                ->route('admin.domain.index')
                ->with('success', 'Configurações de domínio salvas com sucesso!');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível salvar as configurações de domínio.');
            }

            throw $e;
        }
    }

    private function currentDomainSettings(?User $user): array
    {
        $baseDomain = (string) config('app.domain', 'localhost');
        $scheme = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_SCHEME) ?: 'http';
        $port = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_PORT);

        $subdomain = $user?->subdomain ? strtolower(trim((string) $user->subdomain)) : null;
        $customDomain = $user?->custom_domain ? strtolower(trim((string) $user->custom_domain)) : null;
        $activeType = $user?->active_domain_type ?: 'subdomain';

        $subdomainUrl = $subdomain ? $this->buildUrl($scheme, "{$subdomain}.{$baseDomain}", $port) : null;
        $customDomainUrl = $customDomain ? $this->buildUrl($scheme, $customDomain, null) : null;

        return [
            'base_domain' => $baseDomain,
            'active_domain_type' => $activeType,
            'subdomain' => $subdomain,
            'custom_domain' => $customDomain,
            'subdomain_url' => $subdomainUrl,
            'custom_domain_url' => $customDomainUrl,
            'public_url' => $activeType === 'custom' && $customDomainUrl
                ? $customDomainUrl
                : ($subdomainUrl ?? $customDomainUrl),
        ];
    }

    private function buildUrl(string $scheme, string $host, ?int $port): string
    {
        $url = "{$scheme}://{$host}";

        if ($port !== null) {
            $url .= ':' . $port;
        }

        return $url;
    }

    private function normalizeSubdomain(mixed $value): ?string
    {
        $value = Str::slug($this->sanitizeText($value) ?? '', '-');

        return $value === '' ? null : $value;
    }

    private function normalizeCustomDomain(mixed $value): ?string
    {
        $value = $this->sanitizeText($value, false);

        if ($value === null) {
            return null;
        }

        $value = preg_replace('#^https?://#i', '', $value) ?? $value;
        $value = trim($value, "/ \t\n\r\0\x0B");
        $value = explode('/', $value, 2)[0];
        $value = explode(':', $value, 2)[0];
        $value = strtolower($value);

        return $value === '' ? null : $value;
    }
}
