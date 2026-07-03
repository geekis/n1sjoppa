<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * All application permissions.
     *
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'view reports',
        'manage products',
        'manage categories',
        'manage kiosk staff',
        'manage users',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // The admin role always holds every permission.
        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(self::PERMISSIONS);
    }
}
