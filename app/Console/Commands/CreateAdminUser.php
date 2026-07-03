<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('admin:create {email?} {--name=} {--password=}')]
#[Description('Create (or update) an admin user who can log in to /admin.')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email') ?? text('Email', required: true);
        $name = $this->option('name') ?: text('Name', default: 'Admin');

        $plainPassword = $this->option('password') ?: password('Password', required: true);

        if (strlen($plainPassword) < 8) {
            $this->warn('Password is under 8 characters — fine for testing, weak for production.');
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($plainPassword)],
        );

        $wasCreated = $user->wasRecentlyCreated;

        // Make sure the admin role + permissions exist (fresh installs).
        if (! Role::where('name', 'admin')->where('guard_name', 'web')->exists()) {
            $this->call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
        }

        $user->approve();
        $user->assignRole('admin');

        $this->info(($wasCreated ? 'Created' : 'Updated')." admin (approved, role=admin): {$user->email}");

        return self::SUCCESS;
    }
}
