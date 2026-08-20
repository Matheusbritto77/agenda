<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubdomainAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubdomainAvailabilityController extends Controller
{
    public function __invoke(Request $request, SubdomainAvailabilityService $service): JsonResponse
    {
        $subdomain = $service->normalize((string) $request->query('subdomain', ''));
        $ignoreUserId = $request->integer('ignore_user_id') ?: null;

        if ($subdomain === '') {
            return response()->json([
                'available' => false,
                'normalized_subdomain' => '',
                'suggested_subdomain' => '',
                'reason' => 'Informe um subdomínio válido.',
            ]);
        }

        $available = $service->isAvailable($subdomain, $ignoreUserId);

        return response()->json([
            'available' => $available,
            'normalized_subdomain' => $subdomain,
            'suggested_subdomain' => $available ? $subdomain : $service->suggest($subdomain, $ignoreUserId),
            'reason' => $available
                ? 'Subdomínio disponível.'
                : 'Subdomínio já está em uso.',
        ]);
    }
}
