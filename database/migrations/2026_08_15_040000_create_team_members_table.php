<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete()->index();
            $table->string('name');
            $table->string('job_title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('subdomain')->nullable()->index();
            $table->string('custom_domain')->nullable()->index();
            $table->text('bio')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('services')->nullable();
            $table->json('business_hours')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasColumn('appointments', 'team_member_id')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->foreignId('team_member_id')->nullable()->after('service_id')->constrained('team_members')->nullOnDelete()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'team_member_id')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('team_member_id');
            });
        }

        Schema::dropIfExists('team_members');
    }
};
