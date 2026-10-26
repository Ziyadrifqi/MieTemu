<?php

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\BusinessMember;
use App\Models\DiningTable;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::firstOrCreate(
            ['slug' => 'mie-temu-demo'],
            ['name' => 'Mie Temu Demo', 'status' => 'active']
        );

        $branches = [
            [
                'name' => 'Cabang Jakarta Pusat',
                'address' => 'Jl. Contoh No. 1, Jakarta',
            ],
            [
                'name' => 'Cabang Bandung',
                'address' => 'Jl. Contoh No. 2, Bandung',
            ],
        ];

        foreach ($branches as $data) {
            $branch = Branch::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'name' => $data['name'],
                ],
                ['address' => $data['address']]
            );

            // Jam operasional setiap cabang
            for ($day = 1; $day <= 7; $day++) {
                BusinessHour::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'open_time' => '10:00:00',
                        'close_time' => '21:00:00',
                        'is_closed' => false,
                    ]
                );
            }

            // Meja untuk setiap cabang
            foreach (
                [
                    ['T1', 2],
                    ['T2', 2],
                    ['T3', 4],
                    ['T4', 4],
                    ['T5', 6],
                    ['T6', 8],
                ] as [$name, $capacity]
            ) {
                DiningTable::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'name' => $name,
                    ],
                    ['capacity' => $capacity]
                );
            }
        }

        $user = User::where('email', 'test@example.com')->first();

        if ($user) {
            BusinessMember::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'branch_id' => null,
                ],
                ['role' => StaffRole::Admin]
            );
        }

        // Host Jakarta untuk Cabang Jakarta Pusat
        $jakarta = Branch::where('business_id', $business->id)
            ->where('name', 'Cabang Jakarta Pusat')
            ->first();

        $host = User::firstOrCreate(
            ['email' => 'host@example.com'],
            [
                'name' => 'Host Jakarta',
                'password' => 'password',
            ]
        );

        if (! $host->email_verified_at) {
            $host->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        if ($jakarta) {
            BusinessMember::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'user_id' => $host->id,
                    'branch_id' => $jakarta->id,
                ],
                ['role' => StaffRole::Host]
            );
        }
    }
}
