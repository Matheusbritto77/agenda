<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use App\Services\SubdomainAvailabilityService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Throwable;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, SubdomainAvailabilityService $subdomainAvailability): RedirectResponse|JsonResponse
    {
        try {
            $request->merge([
                'name' => $this->sanitizeText($request->input('name')),
                'email' => Str::lower(trim((string) $request->input('email'))),
            ]);

            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
                'phone' => ['nullable', 'string', 'max:30'],
                'country_code' => ['nullable', 'string', 'max:5'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $user = DB::transaction(function () use ($request, $subdomainAvailability): User {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'country_code' => $request->country_code ?? 'BR',
                    'password' => Hash::make($request->password),
                    'must_reset_password' => false,
                    'subdomain' => $this->generateUniqueSubdomain($request->name, $request->email, $subdomainAvailability),
                    'custom_domain' => null,
                    'active_domain_type' => 'subdomain',
                ]);

                $this->seedTenantDefaults($user);

                return $user;
            });

            event(new Registered($user));

            Auth::login($user);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Conta criada com sucesso.',
                    'user' => $user,
                ], 201);
            }

            return redirect()->away(route('admin.dashboard'));
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível criar a conta.');
            }

            throw $e;
        }
    }

    private function generateUniqueSubdomain(string $name, string $email, SubdomainAvailabilityService $subdomainAvailability): string
    {
        $base = Str::slug($name, '-');

        if ($base === '') {
            $base = Str::slug(Str::before($email, '@'), '-');
        }

        if ($base === '') {
            $base = 'empresa';
        }

        $base = Str::limit($base, 50, '');
        $candidate = $base;
        $suffix = 2;

        while (! $subdomainAvailability->isAvailable($candidate)) {
            $candidate = Str::limit($base, max(1, 63 - strlen((string) $suffix) - 1), '') . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function seedTenantDefaults(User $user): void
    {
        foreach ($this->defaultServices() as $service) {
            Service::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $service['name'],
                ],
                $service + [
                    'user_id' => $user->id,
                    'is_active' => true,
                    'slot_duration_minutes' => 30,
                ]
            );
        }

        foreach ($this->defaultBusinessHours() as $businessHour) {
            BusinessHour::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'day_of_week' => $businessHour['day_of_week'],
                    'opens_at' => $businessHour['opens_at'],
                    'closes_at' => $businessHour['closes_at'],
                ],
                $businessHour + [
                    'user_id' => $user->id,
                    'is_active' => true,
                    'slot_duration_minutes' => 30,
                ]
            );
        }
    }

    /**
     * @return array<int, array{name:string,description:string,price:float,duration_minutes:int}>
     */
    private function defaultServices(): array
    {
        return [
            [
                'name' => 'Corte Premium',
                'description' => 'Corte premium com acabamento detalhado e finalização.',
                'price' => 55.00,
                'duration_minutes' => 30,
            ],
            [
                'name' => 'Barba VIP',
                'description' => 'Modelagem de barba com acabamento especial.',
                'price' => 40.00,
                'duration_minutes' => 30,
            ],
            [
                'name' => 'Combo Completo',
                'description' => 'Corte premium + barba VIP em um único atendimento.',
                'price' => 85.00,
                'duration_minutes' => 60,
            ],
        ];
    }

    /**
     * @return array<int, array{day_of_week:int,opens_at:string,closes_at:string,label:string}>
     */
    private function defaultBusinessHours(): array
    {
        return [
            ['day_of_week' => 1, 'opens_at' => '08:00:00', 'closes_at' => '18:00:00', 'label' => 'Segunda'],
            ['day_of_week' => 2, 'opens_at' => '08:00:00', 'closes_at' => '18:00:00', 'label' => 'Terça'],
            ['day_of_week' => 3, 'opens_at' => '08:00:00', 'closes_at' => '18:00:00', 'label' => 'Quarta'],
            ['day_of_week' => 4, 'opens_at' => '08:00:00', 'closes_at' => '18:00:00', 'label' => 'Quinta'],
            ['day_of_week' => 5, 'opens_at' => '08:00:00', 'closes_at' => '18:00:00', 'label' => 'Sexta'],
            ['day_of_week' => 6, 'opens_at' => '08:00:00', 'closes_at' => '18:00:00', 'label' => 'Sábado'],
        ];
    }
}
