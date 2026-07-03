<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        foreach ([
            ['name' => 'Admin', 'email' => 'admin@n1.is', 'password' => 'password'],
            ['name' => 'Egill', 'email' => 'egill@eigind.is', 'password' => 'admin'],
        ] as $data) {
            $user = User::factory()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'approved_at' => now(),
            ]);

            $user->assignRole('admin');
        }

        foreach (['Anna', 'Bjarni', 'Gunnar', 'Helga', 'Kristín', 'Ólafur'] as $name) {
            Staff::create(['name' => $name, 'is_active' => true]);
        }

        $this->call(CatalogSeeder::class);
    }
}
