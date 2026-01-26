<?php

use App\Models\Facility;
use App\Models\User;

test('authenticated users can visit the facilities page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('facility.list'))->assertOk();
});

test('a facility can be created with all fields', function () {
    $this->actingAs(User::factory()->create());

    Livewire\Livewire::test('pages::facility.list')
        ->set('name', 'Sede Centro')
        ->set('address', 'Rua das Flores, 123')
        ->set('phone', '(11) 3333-4444')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('facilities', [
        'name' => 'Sede Centro',
        'address' => 'Rua das Flores, 123',
        'phone' => '(11) 3333-4444',
    ]);
});

test('a facility can be created with only name', function () {
    $this->actingAs(User::factory()->create());

    Livewire\Livewire::test('pages::facility.list')
        ->set('name', 'Sede Simples')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('facilities', [
        'name' => 'Sede Simples',
        'address' => '',
        'phone' => '',
    ]);
});

test('a facility requires a name', function () {
    $this->actingAs(User::factory()->create());

    Livewire\Livewire::test('pages::facility.list')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('a facility can be updated', function () {
    $this->actingAs(User::factory()->create());

    $facility = Facility::factory()->create();

    Livewire\Livewire::test('pages::facility.list')
        ->call('edit', $facility->id)
        ->set('address', 'Novo Endereço, 456')
        ->set('phone', '(21) 5555-6666')
        ->call('save')
        ->assertHasNoErrors();

    $facility->refresh();
    expect($facility->address)->toBe('Novo Endereço, 456');
    expect($facility->phone)->toBe('(21) 5555-6666');
});
