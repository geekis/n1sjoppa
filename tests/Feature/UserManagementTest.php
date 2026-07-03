<?php

use App\Models\User;
use Livewire\Livewire;

it('approves a pending user', function () {
    $this->actingAs(adminUser());
    $pending = User::factory()->create(['approved_at' => null]);

    Livewire::test('pages::admin.users')->call('approve', $pending->id);

    expect($pending->fresh()->isApproved())->toBeTrue();
});

it('revokes approval', function () {
    $this->actingAs(adminUser());
    $other = User::factory()->create(['approved_at' => now()]);

    Livewire::test('pages::admin.users')->call('revoke', $other->id);

    expect($other->fresh()->isApproved())->toBeFalse();
});

it('will not let an admin revoke their own approval', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    Livewire::test('pages::admin.users')->call('revoke', $admin->id);

    expect($admin->fresh()->isApproved())->toBeTrue();
});

it('grants the admin role via the edit form', function () {
    $this->actingAs(adminUser());
    $user = User::factory()->create(['approved_at' => null]);

    Livewire::test('pages::admin.users')
        ->call('edit', $user->id)
        ->set('isAdmin', true)
        ->call('saveUser')
        ->assertSet('showEdit', false);

    $user->refresh();
    expect($user->hasRole('admin'))->toBeTrue()
        ->and($user->can('manage users'))->toBeTrue()
        ->and($user->isApproved())->toBeTrue();
});

it('assigns individual permissions via the edit form', function () {
    $this->actingAs(adminUser());
    $user = User::factory()->create(['approved_at' => now()]);

    Livewire::test('pages::admin.users')
        ->call('edit', $user->id)
        ->set('isAdmin', false)
        ->set('selectedPermissions', ['view reports', 'manage products'])
        ->call('saveUser');

    $user->refresh();
    expect($user->can('view reports'))->toBeTrue()
        ->and($user->can('manage products'))->toBeTrue()
        ->and($user->can('manage users'))->toBeFalse()
        ->and($user->hasRole('admin'))->toBeFalse();
});

it('stops an admin from stripping their own user-management access', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    Livewire::test('pages::admin.users')
        ->call('edit', $admin->id)
        ->set('isAdmin', false)
        ->set('selectedPermissions', ['view reports'])
        ->call('saveUser');

    expect($admin->fresh()->can('manage users'))->toBeTrue();
});

it('creates an approved admin user', function () {
    $this->actingAs(adminUser());

    Livewire::test('pages::admin.users')
        ->call('create')
        ->set('newName', 'Nýr Stjóri')
        ->set('newEmail', 'nyr@example.com')
        ->set('newPassword', 'secret6')
        ->set('newIsAdmin', true)
        ->call('createUser')
        ->assertSet('showCreate', false);

    $user = User::where('email', 'nyr@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->isApproved())->toBeTrue()
        ->and($user->hasRole('admin'))->toBeTrue();
});

it('creates a user with only selected permissions', function () {
    $this->actingAs(adminUser());

    Livewire::test('pages::admin.users')
        ->call('create')
        ->set('newName', 'Skýrslu Skoðari')
        ->set('newEmail', 'skyrsla@example.com')
        ->set('newPassword', 'secret6')
        ->set('newIsAdmin', false)
        ->set('newPermissions', ['view reports'])
        ->call('createUser');

    $user = User::where('email', 'skyrsla@example.com')->first();
    expect($user->can('view reports'))->toBeTrue()
        ->and($user->can('manage products'))->toBeFalse();
});

it('validates a short password on create', function () {
    $this->actingAs(adminUser());

    Livewire::test('pages::admin.users')
        ->call('create')
        ->set('newName', 'X')
        ->set('newEmail', 'x@example.com')
        ->set('newPassword', '123')
        ->call('createUser')
        ->assertHasErrors('newPassword');
});
