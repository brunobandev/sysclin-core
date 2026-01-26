<?php

use App\Enums\UserRole;
use App\Models\User;

test('user role is cast to enum', function () {
    $user = User::factory()->doctor()->create();

    expect($user->role)->toBe(UserRole::Medico);
});

test('default factory user is a secretary', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::Secretario);
});

test('isDoctor returns true for doctors', function () {
    $user = User::factory()->doctor()->create();

    expect($user->isDoctor())->toBeTrue();
    expect($user->isSecretary())->toBeFalse();
    expect($user->isTechnician())->toBeFalse();
});

test('isSecretary returns true for secretaries', function () {
    $user = User::factory()->secretary()->create();

    expect($user->isSecretary())->toBeTrue();
    expect($user->isDoctor())->toBeFalse();
});

test('isTechnician returns true for technicians', function () {
    $user = User::factory()->technician()->create();

    expect($user->isTechnician())->toBeTrue();
    expect($user->isDoctor())->toBeFalse();
});

test('doctor can manage clinical features', function () {
    $user = User::factory()->doctor()->create();

    $this->actingAs($user);

    expect($user->can('manage-medical-records'))->toBeTrue();
    expect($user->can('manage-prescriptions'))->toBeTrue();
    expect($user->can('manage-certificate-templates'))->toBeTrue();
    expect($user->can('manage-prescription-templates'))->toBeTrue();
});

test('secretary cannot manage clinical features', function () {
    $user = User::factory()->secretary()->create();

    $this->actingAs($user);

    expect($user->can('manage-medical-records'))->toBeFalse();
    expect($user->can('manage-prescriptions'))->toBeFalse();
    expect($user->can('manage-certificate-templates'))->toBeFalse();
    expect($user->can('manage-prescription-templates'))->toBeFalse();
});

test('technician cannot manage clinical features', function () {
    $user = User::factory()->technician()->create();

    $this->actingAs($user);

    expect($user->can('manage-medical-records'))->toBeFalse();
    expect($user->can('manage-prescriptions'))->toBeFalse();
});

test('crm_coren is required for doctors and technicians but not secretaries', function () {
    expect(UserRole::Medico->requiresCrmCoren())->toBeTrue();
    expect(UserRole::Tecnico->requiresCrmCoren())->toBeTrue();
    expect(UserRole::Secretario->requiresCrmCoren())->toBeFalse();
});

test('profile page shows role fields', function () {
    $user = User::factory()->doctor()->create();

    $this->actingAs($user);

    $this->get(route('profile.edit'))->assertOk();
});
