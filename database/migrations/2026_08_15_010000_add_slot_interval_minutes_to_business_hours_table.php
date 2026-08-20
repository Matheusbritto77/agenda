<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_hours', function (Blueprint $table) {
            if (! Schema::hasColumn('business_hours', 'slot_interval_minutes')) {
                $table->unsignedSmallInteger('slot_interval_minutes')->default(45)->after('label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_hours', function (Blueprint $table) {
            if (Schema::hasColumn('business_hours', 'slot_interval_minutes')) {
                $table->dropColumn('slot_interval_minutes');
            }
        });
    }
};
