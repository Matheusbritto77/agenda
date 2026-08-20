<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenantTables = [
            'services',
            'appointments',
            'business_hours',
            'blocked_time_slots',
        ];

        foreach ($tenantTables as $tableName) {
            if (! Schema::hasColumn($tableName, 'user_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete()->index();
                });
            }
        }

        $tenantId = User::query()->orderBy('id')->value('id');

        if ($tenantId) {
            DB::table('services')->whereNull('user_id')->update(['user_id' => $tenantId]);
            DB::table('appointments')->whereNull('user_id')->update(['user_id' => $tenantId]);
            DB::table('business_hours')->whereNull('user_id')->update(['user_id' => $tenantId]);
            DB::table('blocked_time_slots')->whereNull('user_id')->update(['user_id' => $tenantId]);
        }
    }

    public function down(): void
    {
        $tenantTables = [
            'services',
            'appointments',
            'business_hours',
            'blocked_time_slots',
        ];

        foreach ($tenantTables as $tableName) {
            if (Schema::hasColumn($tableName, 'user_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('user_id');
                });
            }
        }
    }
};
