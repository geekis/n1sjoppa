<?php

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('approved users are sent from the dashboard into the admin area', function () {
    $this->actingAs(adminUser());

    $this->get(route('dashboard'))->assertRedirect('/admin');
});

test('unapproved users are sent to the pending page', function () {
    seedRolesAndPermissions();
    $this->actingAs(\App\Models\User::factory()->create(['approved_at' => null]));

    $this->get(route('dashboard'))->assertRedirect(route('pending'));
});
