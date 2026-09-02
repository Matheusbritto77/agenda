<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Api\SubdomainAvailabilityController;
use App\Http\Middleware\ResolvePublicBookingTenant;

// Public API Endpoints
Route::middleware([ResolvePublicBookingTenant::class])->group(function () {
    Route::get('/services/{service}/slots', [PublicBookingController::class, 'availableSlots']);
    Route::get('/available-slots', [PublicBookingController::class, 'availableSlots']);
    Route::post('/appointments', [PublicBookingController::class, 'store']);
});

// Admin API Endpoints (Protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/events', [AppointmentController::class, 'events']);
    Route::patch('/admin/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
});

Route::get('/subdomains/availability', SubdomainAvailabilityController::class)->name('api.subdomains.availability');

// WhatsApp Interactive Chat Webhook (SIM / NAO Approval)
Route::post('/webhooks/whatsapp/inbound', [\App\Http\Controllers\Api\WhatsAppInboundWebhookController::class, 'handle'])->name('api.webhooks.whatsapp.inbound');
