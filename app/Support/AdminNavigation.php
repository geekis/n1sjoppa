<?php

namespace App\Support;

use App\Models\User;

class AdminNavigation
{
    /**
     * Admin nav items in display order. Single source of truth for both the
     * sidebar and the route-permission gating.
     *
     * @return array<int, array{permission: string, route: string, label: string, icon: string}>
     */
    public static function items(): array
    {
        return [
            ['permission' => 'view reports', 'route' => 'admin.reports', 'label' => 'Skýrslur', 'icon' => 'chart-bar'],
            ['permission' => 'manage products', 'route' => 'admin.products', 'label' => 'Vörur', 'icon' => 'shopping-bag'],
            ['permission' => 'manage categories', 'route' => 'admin.categories', 'label' => 'Flokkar', 'icon' => 'tag'],
            ['permission' => 'manage kiosk staff', 'route' => 'admin.staff', 'label' => 'Starfsfólk', 'icon' => 'users'],
            ['permission' => 'manage users', 'route' => 'admin.users', 'label' => 'Notendur', 'icon' => 'user-group'],
        ];
    }

    /**
     * Nav items the given user is allowed to see.
     *
     * @return array<int, array{permission: string, route: string, label: string, icon: string}>
     */
    public static function allowed(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return array_values(array_filter(
            self::items(),
            fn (array $item): bool => $user->can($item['permission']),
        ));
    }

    /**
     * Human label for a permission (falls back to the raw name).
     */
    public static function labelForPermission(string $permission): string
    {
        foreach (self::items() as $item) {
            if ($item['permission'] === $permission) {
                return $item['label'];
            }
        }

        return $permission;
    }

    /**
     * URL of the first page this user may access, or the pending page if none.
     */
    public static function firstAllowedUrl(?User $user): string
    {
        $allowed = self::allowed($user);

        if ($allowed === []) {
            return route('pending');
        }

        return route($allowed[0]['route']);
    }
}
