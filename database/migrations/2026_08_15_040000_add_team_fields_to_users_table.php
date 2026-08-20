<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->index();
            }

            if (! Schema::hasColumn('users', 'role_title')) {
                $table->string('role_title')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'must_reset_password')) {
                $table->boolean('must_reset_password')->default(false)->after('role_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'must_reset_password')) {
                $table->dropColumn('must_reset_password');
            }

            if (Schema::hasColumn('users', 'role_title')) {
                $table->dropColumn('role_title');
            }

            if (Schema::hasColumn('users', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }
        });
    }
};
