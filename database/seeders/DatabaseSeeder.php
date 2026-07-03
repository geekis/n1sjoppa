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
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@n1.is',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'Egill',
            'email' => 'egill@eigind.is',
            'password' => Hash::make('admin'),
        ]);

        foreach (['Anna', 'Bjarni', 'Gunnar', 'Helga', 'Kristín', 'Ólafur'] as $name) {
            Staff::create(['name' => $name, 'is_active' => true]);
        }

        $this->call(CatalogSeeder::class);
    }
}
