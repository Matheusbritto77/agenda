<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_reviews', function (Blueprint $table): void {
            if (! Schema::hasColumn('appointment_reviews', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('comment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointment_reviews', function (Blueprint $table): void {
            if (Schema::hasColumn('appointment_reviews', 'is_public')) {
                $table->dropColumn('is_public');
            }
        });
    }
};
