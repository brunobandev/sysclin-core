<?php

use App\Enums\PrescriptionType;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Livewire\Livewire;

test('doctors can visit the prescriptions page', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $this->get(route('prescription.list'))->assertOk();
});

test('secretaries cannot visit the prescriptions page', function () {
    $this->actingAs(User::factory()->secretary()->create());

    $this->get(route('prescription.list'))->assertForbidden();
});

test('technicians cannot visit the prescriptions page', function () {
    $this->actingAs(User::factory()->technician()->create());

    $this->get(route('prescription.list'))->assertForbidden();
});

test('a doctor can create a prescription with items', function () {
    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($doctor);

    Livewire::test('pages::prescription.list')
        ->set('patient_id', $patient->id)
        ->set('type', PrescriptionType::Simples->value)
        ->set('usage_type', 'Oral')
        ->set('disease_cid', 'J11.1')
        ->set('notes', 'Tomar com água')
        ->set('items', [
            ['medication' => 'Paracetamol 750mg', 'quantity' => '20 comprimidos', 'frequency' => '8/8h', 'usage_type' => 'Oral'],
            ['medication' => 'Ibuprofeno 400mg', 'quantity' => '10 comprimidos', 'frequency' => '12/12h', 'usage_type' => 'Oral'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('prescriptions', [
        'patient_id' => $patient->id,
        'user_id' => $doctor->id,
        'type' => PrescriptionType::Simples->value,
    ]);

    $prescription = Prescription::where('patient_id', $patient->id)->first();
    expect($prescription->items)->toHaveCount(2);
    expect($prescription->items->first()->medication)->toBe('Paracetamol 750mg');
});

test('patient_id and type are required', function () {
    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::prescription.list')
        ->set('patient_id', '')
        ->set('type', '')
        ->set('items', [['medication' => 'Test', 'quantity' => '', 'frequency' => '', 'usage_type' => '']])
        ->call('save')
        ->assertHasErrors(['patient_id', 'type']);
});

test('at least one item with medication is required', function () {
    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($doctor);

    Livewire::test('pages::prescription.list')
        ->set('patient_id', $patient->id)
        ->set('type', PrescriptionType::Simples->value)
        ->set('items', [])
        ->call('save')
        ->assertHasErrors(['items']);
});

test('item medication is required', function () {
    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($doctor);

    Livewire::test('pages::prescription.list')
        ->set('patient_id', $patient->id)
        ->set('type', PrescriptionType::Simples->value)
        ->set('items', [['medication' => '', 'quantity' => '', 'frequency' => '', 'usage_type' => '']])
        ->call('save')
        ->assertHasErrors(['items.0.medication']);
});

test('a prescription can be updated', function () {
    $doctor = User::factory()->doctor()->create();
    $prescription = Prescription::factory()->create(['user_id' => $doctor->id]);
    $prescription->items()->create(['medication' => 'Old Med', 'quantity' => '10']);

    $this->actingAs($doctor);

    Livewire::test('pages::prescription.list')
        ->call('edit', $prescription->id)
        ->set('notes', 'Notas atualizadas')
        ->set('items', [
            ['medication' => 'New Med', 'quantity' => '20', 'frequency' => '6/6h', 'usage_type' => 'Oral'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $prescription->refresh();
    expect($prescription->notes)->toBe('Notas atualizadas');
    expect($prescription->items)->toHaveCount(1);
    expect($prescription->items->first()->medication)->toBe('New Med');
});

test('a prescription can be deleted', function () {
    $doctor = User::factory()->doctor()->create();
    $prescription = Prescription::factory()->create(['user_id' => $doctor->id]);
    $prescription->items()->create(['medication' => 'Test']);

    $this->actingAs($doctor);

    Livewire::test('pages::prescription.list')
        ->call('delete', $prescription->id);

    $this->assertDatabaseMissing('prescriptions', ['id' => $prescription->id]);
    $this->assertDatabaseCount('prescription_items', 0);
});

test('items can be dynamically added and removed', function () {
    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::prescription.list')
        ->call('create')
        ->assertCount('items', 1)
        ->call('addItem')
        ->assertCount('items', 2)
        ->call('removeItem', 0)
        ->assertCount('items', 1);
});

test('controle especial type can be set', function () {
    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($doctor);

    Livewire::test('pages::prescription.list')
        ->set('patient_id', $patient->id)
        ->set('type', PrescriptionType::ControleEspecial->value)
        ->set('items', [['medication' => 'Diazepam 5mg', 'quantity' => '30', 'frequency' => '1x ao dia', 'usage_type' => 'Oral']])
        ->call('save')
        ->assertHasNoErrors();

    $prescription = Prescription::where('patient_id', $patient->id)->first();
    expect($prescription->type)->toBe(PrescriptionType::ControleEspecial);
});
