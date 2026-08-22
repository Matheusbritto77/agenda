<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = $this->serviceQuery($request)->orderBy('name', 'asc')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'services' => $services,
            ]);
        }

        return Inertia::render('Admin/Services/Index', compact('services'));
    }

    public function create(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Use this endpoint for rendering the create form in the frontend.',
            ]);
        }

        return Inertia::render('Admin/Services/Create');
    }

    public function store(Request $request)
    {
        try {
            if (! $request->user()->hasPermission('services.prices') && $request->filled('price') && (float) $request->input('price') !== 0.0) {
                throw ValidationException::withMessages([
                    'price' => 'Você não tem permissão para alterar a tabela de preços.',
                ]);
            }

            $validated = $this->validateServiceRequest($request);
            $service = Service::create($this->payloadForService($request, $validated));

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Serviço criado com sucesso!',
                    'service' => $service->fresh(),
                    'services' => $this->servicesSnapshot($request),
                ], 201);
            }

            return redirect()->route('admin.services.index')->with('success', 'Serviço criado com sucesso!');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível criar o serviço.');
            }

            throw $e;
        }
    }

    public function edit(Request $request, Service $service)
    {
        $companyId = $request->user()?->parent_id ?? $request->user()?->id;
        abort_unless((int) $companyId === (int) $service->user_id, 404);

        if ($request->expectsJson()) {
            return response()->json([
                'service' => $service,
            ]);
        }

        return Inertia::render('Admin/Services/Edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        try {
            $companyId = $request->user()?->parent_id ?? $request->user()?->id;
            abort_unless((int) $companyId === (int) $service->user_id, 404);

            if (! $request->user()->hasPermission('services.prices') && $request->filled('price') && (float) $request->input('price') !== (float) $service->price) {
                throw ValidationException::withMessages([
                    'price' => 'Você não tem permissão para alterar a tabela de preços.',
                ]);
            }

            $validated = $this->validateServiceRequest($request, $service);
            $service->update($this->payloadForService($request, $validated, $service));

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Serviço atualizado com sucesso!',
                    'service' => $service->fresh(),
                    'services' => $this->servicesSnapshot($request),
                ]);
            }

            return redirect()->route('admin.services.index')->with('success', 'Serviço atualizado com sucesso!');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível atualizar o serviço.');
            }

            throw $e;
        }
    }

    public function toggleStatus(Request $request, Service $service)
    {
        try {
            $companyId = $request->user()?->parent_id ?? $request->user()?->id;
            abort_unless((int) $companyId === (int) $service->user_id, 404);

            $service->update(['is_active' => ! $service->is_active]);

            $statusMsg = $service->is_active ? 'ativado' : 'desativado';

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, "Serviço '{$service->name}' foi {$statusMsg}.", [
                    'service' => $service->fresh(),
                    'services' => $this->servicesSnapshot($request),
                ]);
            }

            return back()->with('success', "Serviço '{$service->name}' foi {$statusMsg}.");
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível alterar o status do serviço.');
            }

            throw $e;
        }
    }

    public function destroy(Request $request, Service $service)
    {
        try {
            $companyId = $request->user()?->parent_id ?? $request->user()?->id;
            abort_unless((int) $companyId === (int) $service->user_id, 404);

            $deletedService = $service->toArray();
            $service->delete();

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Serviço removido com sucesso.', [
                    'service' => $deletedService,
                    'services' => $this->servicesSnapshot($request),
                ]);
            }

            return redirect()->route('admin.services.index')->with('success', 'Serviço removido com sucesso.');
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível remover o serviço.');
            }

            throw $e;
        }
    }

    private function validateServiceRequest(Request $request, ?Service $service = null): array
    {
        return Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'slot_duration_minutes' => ['nullable', 'integer', Rule::in([15, 30, 45, 60, 90, 120])],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'image_file' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp,jpg', 'max:10240'],
        ])->after(function ($validator) use ($request): void {
            if ($request->filled('image_url') && $request->hasFile('image_file')) {
                $validator->errors()->add('image_file', 'Envie apenas uma imagem por vez: arquivo ou URL.');
            }
        })->validate();
    }

    private function payloadForService(Request $request, array $validated, ?Service $service = null): array
    {
        $tenantId = $this->tenantId($request, $service);

        $payload = [
            'user_id' => $tenantId,
            'name' => $this->sanitizeText($validated['name']) ?? '',
            'description' => isset($validated['description']) ? $this->sanitizeText($validated['description']) : null,
            'price' => $validated['price'],
            'duration_minutes' => $validated['duration_minutes'],
            'slot_duration_minutes' => $validated['slot_duration_minutes'] ?? $service?->slot_duration_minutes ?? 30,
        ];

        if ($request->hasFile('image_file')) {
            $payload['image_path'] = $this->storeUploadedImage($request, $service);
            return $payload;
        }

        if ($request->filled('image_url')) {
            $this->deleteStoredImageIfNeeded($service);
            $payload['image_path'] = trim((string) $validated['image_url']);
            return $payload;
        }

        if ($service !== null) {
            $payload['image_path'] = $service->image_path;
        }

        return $payload;
    }

    private function storeUploadedImage(Request $request, ?Service $service = null): string
    {
        $this->deleteStoredImageIfNeeded($service);

        return $request->file('image_file')->store('services', 'public');
    }

    private function deleteStoredImageIfNeeded(?Service $service = null): void
    {
        if ($service && $service->image_path && ! filter_var($service->image_path, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($service->image_path);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function servicesSnapshot(Request $request): array
    {
        return Service::query()
            ->where('services.user_id', $this->tenantId($request))
            ->orderBy('name', 'asc')
            ->get()
            ->values()
            ->all();
    }

    private function serviceQuery(Request $request)
    {
        return Service::query()->where('services.user_id', $this->tenantId($request));
    }

    private function tenantId(Request $request, ?Service $service = null): int
    {
        $user = $request->user();
        $tenantId = $user?->parent_id ?? $user?->id ?? $service?->user_id;

        if (! is_int($tenantId) && ! ctype_digit((string) $tenantId)) {
            abort(403, 'Tenant indisponível.');
        }

        return (int) $tenantId;
    }
}
