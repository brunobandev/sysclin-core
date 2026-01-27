<?php

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\AppointmentType;
use App\Models\HealthInsurance;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use Livewire\Livewire;

test('authenticated users can visit the appointment page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('appointment.list'))->assertOk();
});

test('the appointment page renders the calendar heading', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('appointment.list'))
        ->assertOk()
        ->assertSee('Agenda');
});

test('an appointment can be created with valid data', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $type = AppointmentType::factory()->create();
    $status = AppointmentStatus::factory()->create();
    $insurance = HealthInsurance::factory()->create();

    Livewire::test('pages::appointment.list')
        ->set('patient_id', (string) $patient->id)
        ->set('user_id', (string) $user->id)
        ->set('room_id', (string) $room->id)
        ->set('type_id', (string) $type->id)
        ->set('status_id', (string) $status->id)
        ->set('health_insurance_id', (string) $insurance->id)
        ->set('start_at', '2026-02-01T09:00')
        ->set('end_at', '2026-02-01T10:00')
        ->set('notes', 'Primeira consulta')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'room_id' => $room->id,
    ]);
});

test('all required fields are validated', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::appointment.list')
        ->set('patient_id', '')
        ->set('user_id', '')
        ->set('room_id', '')
        ->set('type_id', '')
        ->set('status_id', '')
        ->set('health_insurance_id', '')
        ->set('start_at', '')
        ->set('end_at', '')
        ->call('save')
        ->assertHasErrors([
            'patient_id', 'user_id', 'room_id',
            'type_id', 'status_id', 'health_insurance_id',
            'start_at', 'end_at',
        ]);
});

test('end_at must be after start_at', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $type = AppointmentType::factory()->create();
    $status = AppointmentStatus::factory()->create();
    $insurance = HealthInsurance::factory()->create();

    Livewire::test('pages::appointment.list')
        ->set('patient_id', (string) $patient->id)
        ->set('user_id', (string) $user->id)
        ->set('room_id', (string) $room->id)
        ->set('type_id', (string) $type->id)
        ->set('status_id', (string) $status->id)
        ->set('health_insurance_id', (string) $insurance->id)
        ->set('start_at', '2026-02-01T10:00')
        ->set('end_at', '2026-02-01T09:00')
        ->call('save')
        ->assertHasErrors(['end_at']);
});

test('an appointment can be updated', function () {
    $this->actingAs(User::factory()->create());

    $appointment = Appointment::factory()->create();

    Livewire::test('pages::appointment.list')
        ->call('edit', $appointment->id)
        ->set('notes', 'Observacao atualizada')
        ->call('save')
        ->assertHasNoErrors();

    expect($appointment->fresh()->notes)->toBe('Observacao atualizada');
});

test('create method pre-fills start time', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::appointment.list')
        ->call('create', '2026-02-01T09:00')
        ->assertSet('start_at', '2026-02-01T09:00')
        ->assertSet('end_at', '2026-02-01T10:00');
});

test('create method pre-fills room id when provided', function () {
    $this->actingAs(User::factory()->create());

    $room = Room::factory()->create();

    Livewire::test('pages::appointment.list')
        ->call('create', '2026-02-01T09:00', (string) $room->id)
        ->assertSet('room_id', (string) $room->id);
});

test('events include resourceId mapped to room_id', function () {
    $this->actingAs(User::factory()->create());

    $room = Room::factory()->create();
    $appointment = Appointment::factory()->create(['room_id' => $room->id]);

    $component = Livewire::test('pages::appointment.list');
    $events = $component->instance()->events;

    $event = collect($events)->firstWhere('id', $appointment->id);
    expect($event['resourceId'])->toBe((string) $room->id);
});

test('calendar resources are derived from rooms', function () {
    $this->actingAs(User::factory()->create());

    $room = Room::factory()->create(['name' => 'Sala 01']);

    $component = Livewire::test('pages::appointment.list');
    $resources = $component->instance()->calendarResources;

    $resource = collect($resources)->firstWhere('id', (string) $room->id);
    expect($resource)->not->toBeNull();
    expect($resource['title'])->toBe('Sala 01');
});

test('a patient can be quick-created from the appointment page', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::appointment.list')
        ->call('createQuickPatient')
        ->set('new_patient_name', 'Carlos Souza')
        ->set('new_patient_dob', '1990-05-15')
        ->set('new_patient_phone', '(11) 99999-0000')
        ->set('new_patient_gender', 'Masculino')
        ->call('saveQuickPatient')
        ->assertHasNoErrors();

    $patient = Patient::where('name', 'Carlos Souza')->first();
    expect($patient)->not->toBeNull();
    expect($patient->dob)->toBe('1990-05-15');
});

test('quick-create patient auto-selects the new patient', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test('pages::appointment.list')
        ->call('createQuickPatient')
        ->set('new_patient_name', 'Ana Lima')
        ->set('new_patient_dob', '1985-03-20')
        ->call('saveQuickPatient')
        ->assertHasNoErrors();

    $patient = Patient::where('name', 'Ana Lima')->first();
    $component->assertSet('patient_id', (string) $patient->id);
});

test('quick-create patient validates required fields', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::appointment.list')
        ->call('createQuickPatient')
        ->set('new_patient_name', '')
        ->set('new_patient_dob', '')
        ->call('saveQuickPatient')
        ->assertHasErrors(['new_patient_name', 'new_patient_dob']);
});
