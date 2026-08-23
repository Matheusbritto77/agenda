<?php

use App\Http\Controllers\Admin\AdminAppointmentController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\BlockedTimeSlotController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\BusinessHourController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\FinancialController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Client\ClientAuthController;
use App\Http\Controllers\Client\ClientPasswordController;
use App\Http\Controllers\Client\ClientPortalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Middleware\ResolvePublicBookingTenant;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Client Booking & SaaS Landing Routes
Route::get('/landing', [PublicBookingController::class, 'landing'])->name('landing');

Route::prefix('cliente')->name('client.')->group(function (): void {
    Route::middleware('guest:client')->group(function (): void {
        Route::get('/entrar', [ClientAuthController::class, 'create'])->name('login');
        Route::post('/entrar', [ClientAuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    });

    Route::middleware('auth:client')->group(function (): void {
        Route::get('/senha', [ClientPasswordController::class, 'edit'])->name('password.edit');
        Route::put('/senha', [ClientPasswordController::class, 'update'])->name('password.update');
        Route::post('/sair', [ClientAuthController::class, 'destroy'])->name('logout');

        Route::middleware('client.password.reset')->group(function (): void {
            Route::get('/', [ClientPortalController::class, 'index'])->name('dashboard');
            Route::put('/agendamentos/{appointment}/avaliacao', [ClientPortalController::class, 'review'])->name('reviews.store');
        });
    });
});

$rootHosts = array_values(array_unique(array_filter([
    'localhost',
    '127.0.0.1',
    '::1',
    strtolower((string) config('app.domain', '')),
    strtolower((string) (parse_url((string) config('app.url', ''), PHP_URL_HOST) ?: '')),
])));

$isRootHost = static function (string $host) use ($rootHosts): bool {
    return in_array(strtolower($host), $rootHosts, true);
};

Route::get('/', function (Request $request, PublicBookingController $controller) use ($isRootHost) {
    if ($isRootHost($request->getHost())) {
        return Inertia::render('Welcome');
    }

    return app(ResolvePublicBookingTenant::class)->handle(
        $request,
        static fn (Request $request) => $controller->index($request)
    );
})->name('booking.index');

Route::middleware([ResolvePublicBookingTenant::class])->group(function () {
    Route::get('/available-slots', [PublicBookingController::class, 'availableSlots'])->name('booking.slots');
    Route::post('/booking', [PublicBookingController::class, 'store'])->name('booking.store');
    Route::get('/booking', function () {
        return redirect()->route('booking.index');
    });
});

// Public Payment Routes
Route::post('/payment/pix', [PaymentController::class, 'createPixForAppointment'])->name('payment.pix.create');
Route::get('/payment/{payment}/status', [PaymentController::class, 'checkStatus'])->name('payment.status');
Route::post('/webhooks/mercadopago', [PaymentController::class, 'webhook'])->name('webhooks.mercadopago');

Route::get('/s/{subdomain}', function (Request $request, string $subdomain) {
    $slug = strtolower(trim($subdomain));

    $tenant = User::query()
        ->where(function ($query) use ($slug): void {
            $query->where('subdomain', $slug)
                ->orWhere('custom_domain', $slug);
        })
        ->first();

    if ($tenant) {
        return redirect()->away($tenant->publicBookingUrl());
    }

    $member = TeamMember::query()
        ->where(function ($query) use ($slug): void {
            $query->where('subdomain', $slug)
                ->orWhere('custom_domain', $slug);
        })
        ->first();

    if ($member) {
        return redirect()->away($member->publicBookingUrl());
    }

    abort(404, 'Estabelecimento ou profissional não encontrado.');
})->where('subdomain', '[a-z0-9-]+')->name('booking.shortcut');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update.upload');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Routes (Protected)
Route::middleware(['auth', 'must.reset.password'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::middleware('permission:reports.revenue')->group(function () {
        Route::get('/financial', [FinancialController::class, 'index'])->name('financial.index');
        Route::post('/financial/transactions', [FinancialController::class, 'storeTransaction'])->name('financial.transactions.store');
        Route::put('/financial/transactions/{transaction}', [FinancialController::class, 'updateTransaction'])->name('financial.transactions.update');
        Route::delete('/financial/transactions/{transaction}', [FinancialController::class, 'destroyTransaction'])->name('financial.transactions.destroy');
        Route::patch('/financial/transactions/{transaction}/toggle-status', [FinancialController::class, 'toggleTransactionStatus'])->name('financial.transactions.toggle-status');
    });
    Route::middleware('permission:schedules.view')->get('/business-hours', [BusinessHourController::class, 'index'])->name('business-hours.index');
    Route::middleware('permission:schedules.manage')->post('/business-hours', [BusinessHourController::class, 'store'])->name('business-hours.store');
    Route::middleware('permission:schedules.manage,schedules.breaks')->put('/business-hours/{id}', [BusinessHourController::class, 'update'])->name('business-hours.update');
    Route::middleware('permission:schedules.manage')->delete('/business-hours/{id}', [BusinessHourController::class, 'destroy'])->name('business-hours.destroy');
    Route::middleware('permission:schedules.blocks')->post('/business-hours/blocks', [BusinessHourController::class, 'storeBlock'])->name('business-hours.blocks.store');
    Route::middleware('permission:schedules.blocks')->put('/business-hours/blocks/{blockedTimeSlot}', [BusinessHourController::class, 'updateBlock'])->name('business-hours.blocks.update');
    Route::middleware('permission:schedules.blocks')->delete('/business-hours/blocks/{blockedTimeSlot}', [BusinessHourController::class, 'destroyBlock'])->name('business-hours.blocks.destroy');
    Route::middleware('permission:schedules.blocks')->put('/blocks/{id}', [BlockedTimeSlotController::class, 'update'])->name('blocks.update');

    // Services CRUD & Toggle
    Route::middleware('permission:services.view')->get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::middleware('permission:services.create')->get('/services/create', [ServiceController::class, 'create'])->name('services.create');
    Route::middleware('permission:services.create')->post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::middleware('permission:services.edit')->get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
    Route::middleware('permission:services.edit')->match(['put', 'post'], '/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::middleware('permission:services.delete')->delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
    Route::middleware('permission:services.delete')->patch('/services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');

    // Appointments Management & Events API
    Route::middleware('permission:appointments.create')->post('/appointments', [AdminAppointmentController::class, 'store'])->name('appointments.store');
    Route::middleware('permission:appointments.view')->get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::middleware('permission:appointments.view')->get('/appointments/events', [AppointmentController::class, 'events'])->name('appointments.events');
    Route::middleware('permission:appointments.view')->get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::middleware('permission:appointments.edit')->patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.update-status');

    // Team CRUD & Toggle
    Route::middleware('permission:team.view')->get('/team', [TeamMemberController::class, 'index'])->name('team.index');
    Route::middleware('permission:team.create')->post('/team', [TeamMemberController::class, 'store'])->name('team.store');
    Route::middleware('permission:team.edit')->match(['put', 'post'], '/team/{teamMember}', [TeamMemberController::class, 'update'])->name('team.update');
    Route::middleware('permission:team.delete')->delete('/team/{teamMember}', [TeamMemberController::class, 'destroy'])->name('team.destroy');
    Route::middleware('permission:team.delete')->patch('/team/{teamMember}/toggle-status', [TeamMemberController::class, 'toggleStatus'])->name('team.toggle-status');
    Route::middleware('permission:team.edit')->post('/team/{teamMember}/reset-password', [TeamMemberController::class, 'resetPassword'])->name('team.reset-password');

    Route::middleware('permission:settings.domain')->get('/domain', [DomainController::class, 'index'])->name('domain.index');
    Route::middleware('permission:settings.domain')->post('/domain', [DomainController::class, 'store'])->name('domain.update');

    // Integrations Settings
    Route::middleware('permission:integrations.view')->get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
    Route::middleware('permission:integrations.manage')->post('/integrations/payments', [IntegrationController::class, 'updatePaymentSettings'])->name('integrations.payments.update');

    // Branding & Visual Identity
    Route::middleware('permission:branding.view')->get('/branding', [BrandingController::class, 'index'])->name('branding.index');
    Route::middleware('permission:branding.manage')->post('/branding', [BrandingController::class, 'update'])->name('branding.update');

    // Roles & Permissions Settings
    Route::middleware('permission:settings.roles')->get('/roles', [RolePermissionController::class, 'index'])->name('roles.index');
    Route::middleware('permission:settings.roles')->post('/roles/permissions', [RolePermissionController::class, 'updatePermissions'])->name('roles.permissions.update');
    Route::middleware('permission:settings.roles')->patch('/roles/team-members/{teamMember}/role', [RolePermissionController::class, 'updateMemberRole'])->name('roles.team-member.update-role');

    // Force Password Change Routes
    Route::get('/force-password-change', [PasswordController::class, 'showForceChangeForm'])->name('force-password-change.show');
    Route::post('/force-password-change', [PasswordController::class, 'forceChangePassword'])->name('force-password-change.submit');
});

require __DIR__.'/auth.php';
