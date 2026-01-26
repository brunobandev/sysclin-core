<?php

use App\Models\PrescriptionTemplate;
use App\Models\User;
use Livewire\Livewire;

test('doctors can visit the prescription templates page', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $this->get(route('prescription-template.list'))->assertOk();
});

test('secretaries cannot visit the prescription templates page', function () {
    $this->actingAs(User::factory()->secretary()->create());

    $this->get(route('prescription-template.list'))->assertForbidden();
});

test('a doctor can create a prescription template', function () {
    $doctor = User::factory()->doctor()->create();

    $this->actingAs($doctor);

    Livewire::test('pages::prescription-template.list')
        ->set('name', 'Gripe Comum')
        ->set('items', [
            ['medication' => 'Paracetamol 750mg', 'quantity' => '20', 'frequency' => '8/8h', 'usage_type' => 'Oral'],
            ['medication' => 'Loratadina 10mg', 'quantity' => '10', 'frequency' => '1x ao dia', 'usage_type' => 'Oral'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('prescription_templates', [
        'user_id' => $doctor->id,
        'name' => 'Gripe Comum',
    ]);

    $template = PrescriptionTemplate::where('user_id', $doctor->id)->first();
    expect($template->items)->toHaveCount(2);
});

test('template name is required', function () {
    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::prescription-template.list')
        ->set('name', '')
        ->set('items', [['medication' => 'Test', 'quantity' => '', 'frequency' => '', 'usage_type' => '']])
        ->call('save')
        ->assertHasErrors(['name']);
});

test('at least one item is required', function () {
    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::prescription-template.list')
        ->set('name', 'Test Template')
        ->set('items', [])
        ->call('save')
        ->assertHasErrors(['items']);
});

test('a template can be updated', function () {
    $doctor = User::factory()->doctor()->create();
    $template = PrescriptionTemplate::factory()->create(['user_id' => $doctor->id]);
    $template->items()->create(['medication' => 'Old Med']);

    $this->actingAs($doctor);

    Livewire::test('pages::prescription-template.list')
        ->call('edit', $template->id)
        ->set('name', 'Nome Atualizado')
        ->set('items', [['medication' => 'New Med', 'quantity' => '30', 'frequency' => '12/12h', 'usage_type' => 'Oral']])
        ->call('save')
        ->assertHasNoErrors();

    $template->refresh();
    expect($template->name)->toBe('Nome Atualizado');
    expect($template->items)->toHaveCount(1);
    expect($template->items->first()->medication)->toBe('New Med');
});

test('a template can be deleted', function () {
    $doctor = User::factory()->doctor()->create();
    $template = PrescriptionTemplate::factory()->create(['user_id' => $doctor->id]);
    $template->items()->create(['medication' => 'Test']);

    $this->actingAs($doctor);

    Livewire::test('pages::prescription-template.list')
        ->call('delete', $template->id);

    $this->assertDatabaseMissing('prescription_templates', ['id' => $template->id]);
    $this->assertDatabaseCount('prescription_template_items', 0);
});

test('templates are scoped to the authenticated user', function () {
    $doctor1 = User::factory()->doctor()->create();
    $doctor2 = User::factory()->doctor()->create();

    PrescriptionTemplate::factory()->create(['user_id' => $doctor1->id, 'name' => 'Template 1']);
    PrescriptionTemplate::factory()->create(['user_id' => $doctor2->id, 'name' => 'Template 2']);

    $this->actingAs($doctor1);

    $component = Livewire::test('pages::prescription-template.list');
    $templates = $component->get('templates');

    expect($templates)->toHaveCount(1);
    expect($templates->first()->name)->toBe('Template 1');
});

test('a template can be loaded into a prescription', function () {
    $doctor = User::factory()->doctor()->create();
    $template = PrescriptionTemplate::factory()->create(['user_id' => $doctor->id]);
    $template->items()->create(['medication' => 'Paracetamol', 'quantity' => '20', 'frequency' => '8/8h', 'usage_type' => 'Oral']);
    $template->items()->create(['medication' => 'Ibuprofeno', 'quantity' => '10', 'frequency' => '12/12h', 'usage_type' => 'Oral']);

    $this->actingAs($doctor);

    Livewire::test('pages::prescription.list')
        ->call('loadTemplate', $template->id)
        ->assertCount('items', 2)
        ->assertSet('items.0.medication', 'Paracetamol')
        ->assertSet('items.1.medication', 'Ibuprofeno');
});
