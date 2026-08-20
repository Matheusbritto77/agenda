<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@agendae.com'],
            [
                'name' => 'Administrador Agendae',
                'password' => Hash::make('password'),
                'subdomain' => 'agendae',
                'custom_domain' => null,
                'active_domain_type' => 'subdomain',
            ]
        );

        $demoTenant = User::updateOrCreate(
            ['email' => 'demo@agendae.com'],
            [
                'name' => 'Barbearia VIP',
                'password' => Hash::make('demo123'),
                'subdomain' => 'barbearia-vip',
                'custom_domain' => null,
                'active_domain_type' => 'subdomain',
            ]
        );



        $businessHours = [
            ['day_of_week' => Carbon::MONDAY, 'opens_at' => '08:00:00', 'closes_at' => '12:00:00', 'label' => 'Manhã'],
            ['day_of_week' => Carbon::MONDAY, 'opens_at' => '13:00:00', 'closes_at' => '18:00:00', 'label' => 'Tarde'],
            ['day_of_week' => Carbon::TUESDAY, 'opens_at' => '08:00:00', 'closes_at' => '12:00:00', 'label' => 'Manhã'],
            ['day_of_week' => Carbon::TUESDAY, 'opens_at' => '13:00:00', 'closes_at' => '18:00:00', 'label' => 'Tarde'],
            ['day_of_week' => Carbon::WEDNESDAY, 'opens_at' => '08:00:00', 'closes_at' => '12:00:00', 'label' => 'Manhã'],
            ['day_of_week' => Carbon::WEDNESDAY, 'opens_at' => '13:00:00', 'closes_at' => '18:00:00', 'label' => 'Tarde'],
            ['day_of_week' => Carbon::THURSDAY, 'opens_at' => '08:00:00', 'closes_at' => '12:00:00', 'label' => 'Manhã'],
            ['day_of_week' => Carbon::THURSDAY, 'opens_at' => '13:00:00', 'closes_at' => '18:00:00', 'label' => 'Tarde'],
            ['day_of_week' => Carbon::FRIDAY, 'opens_at' => '08:00:00', 'closes_at' => '12:00:00', 'label' => 'Manhã'],
            ['day_of_week' => Carbon::FRIDAY, 'opens_at' => '13:00:00', 'closes_at' => '18:00:00', 'label' => 'Tarde'],
            ['day_of_week' => Carbon::SATURDAY, 'opens_at' => '08:00:00', 'closes_at' => '12:00:00', 'label' => 'Sábado'],
        ];

        foreach ($businessHours as $businessHour) {
            BusinessHour::updateOrCreate(
                [
                    'user_id' => $demoTenant->id,
                    'day_of_week' => $businessHour['day_of_week'],
                    'opens_at' => $businessHour['opens_at'],
                    'closes_at' => $businessHour['closes_at'],
                ],
                $businessHour + [
                    'user_id' => $demoTenant->id,
                    'is_active' => true,
                    'slot_duration_minutes' => 30,
                ]
            );
        }
    }
}
