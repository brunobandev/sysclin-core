<?php

use App\Models\HealthInsurance;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Room;
use App\Models\AppointmentType;
use App\Models\AppointmentStatus;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Agenda')]
class extends Component {
    public ?Appointment $editing = null;

    // Form fields
    public string $patient_id = '';
    public string $user_id = '';
    public string $room_id = '';
    public string $type_id = '';
    public string $status_id = '';
    public string $health_insurance_id = '';
    public string $start_at = '';
    public string $end_at = '';
    public string $notes = '';

    #[Computed]
    public function patients()
    {
        return Patient::orderBy('name')->get();
    }

    #[Computed]
    public function users()
    {
        return User::orderBy('name')->get();
    }

    #[Computed]
    public function rooms()
    {
        return Room::orderBy('name')->get();
    }

    #[Computed]
    public function types()
    {
        return AppointmentType::all();
    }

    #[Computed]
    public function statuses()
    {
        return AppointmentStatus::all();
    }

    #[Computed]
    public function healthInsurances()
    {
        return HealthInsurance::orderBy('name')->get();
    }

    #[Computed]
    public function events()
    {
        return Appointment::with(['patient', 'type'])
            ->get()
            ->map(fn($app) => [
                'id' => $app->id,
                'title' => $app->patient->name . ' (' . $app->type->name . ')',
                'start' => $app->start_at->toIso8601String(),
                'end' => $app->end_at->toIso8601String(),
                'backgroundColor' => $app->type->color ?? '#3b82f6',
            ]);
    }

    public function create($start = null)
    {
        $this->editing = null;
        $this->reset(['patient_id', 'user_id', 'room_id', 'type_id', 'status_id', 'health_insurance_id', 'notes']);
        $this->start_at = $start ?? now()->format('Y-m-d\TH:i');
        $this->end_at = $start ? Carbon::parse($start)->addHour()->format('Y-m-d\TH:i') : now()->addHour()->format('Y-m-d\TH:i');

        $this->modal('appointment-form')->show();
    }

    public function edit(Appointment $appointment)
    {
        $this->editing = $appointment;
        $this->patient_id = (string)$appointment->patient_id;
        $this->user_id = (string)$appointment->user_id;
        $this->room_id = (string)$appointment->room_id;
        $this->type_id = (string)$appointment->appointment_type_id;
        $this->status_id = (string)$appointment->appointment_status_id;
        $this->health_insurance_id = (string)$appointment->health_insurance_id;
        $this->start_at = $appointment->start_at->format('Y-m-d\TH:i');
        $this->end_at = $appointment->end_at->format('Y-m-d\TH:i');
        $this->notes = $appointment->notes ?? '';

        $this->modal('appointment-form')->show();
    }

    public function save()
    {
        $validated = $this->validate([
            'patient_id' => 'required|exists:patients,id',
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'type_id' => 'required|exists:appointment_types,id',
            'status_id' => 'required|exists:appointment_statuses,id',
            'health_insurance_id' => 'required|exists:health_insurances,id',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'patient_id' => $this->patient_id,
            'user_id' => $this->user_id,
            'room_id' => $this->room_id,
            'appointment_type_id' => $this->type_id,
            'appointment_status_id' => $this->status_id,
            'health_insurance_id' => $this->health_insurance_id,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'notes' => $this->notes,
        ];

        if ($this->editing) {
            $this->editing->update($data);
            Flux::toast(text: 'Agendamento atualizado.', variant: 'success');
        } else {
            Appointment::create($data);
            Flux::toast(text: 'Agendamento realizado.', variant: 'success');
        }

        $this->modal('appointment-form')->close();
        $this->dispatch('refreshCalendar');
    }

    public function getEvents()
    {
        return $this->events;
    }
};
?>

<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Agenda</flux:heading>
            <flux:text class="mt-2 text-base">Gerencie consultas e horários</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">Novo agendamento</flux:button>
    </div>

    <flux:separator variant="subtle" class="my-6"/>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
         wire:ignore
         x-data="{
            calendar: null,
            init() {
                this.calendar = new FullCalendar.Calendar(this.$refs.calendar, {
                    initialView: 'timeGridWeek',
                    locale: 'pt-br',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: @js($this->events),
                    editable: true,
                    selectable: true,
                    select: (info) => {
                        $wire.create(info.startStr.slice(0, 16));
                    },
                    eventClick: (info) => {
                        $wire.edit(info.event.id);
                    },
                });
                this.calendar.render();

                Livewire.on('refreshCalendar', () => {
                    $wire.getEvents().then(events => {
                        this.calendar.removeAllEvents();
                        this.calendar.addEventSource(events);
                    });
                });
            }
         }">
        <div x-ref="calendar"></div>
    </div>

    <flux:modal name="appointment-form" flyout variant="floating" class="md:w-lg">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Editar agendamento' : 'Novo agendamento' }}</flux:heading>
                <flux:subheading>Preencha os detalhes da consulta.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <flux:select wire:model="patient_id" label="Paciente" placeholder="Selecione..." filterable>
                    @foreach ($this->patients as $patient)
                        <flux:select.option :value="$patient->id">{{ $patient->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="user_id" label="Profissional" placeholder="Selecione..." filterable>
                    @foreach ($this->users as $user)
                        <flux:select.option :value="$user->id">{{ $user->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="room_id" label="Sala" placeholder="Selecione...">
                    @foreach ($this->rooms as $room)
                        <flux:select.option :value="$room->id">{{ $room->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="health_insurance_id" label="Plano de saúde" placeholder="Selecione...">
                    @foreach ($this->healthInsurances as $insurance)
                        <flux:select.option :value="$insurance->id">{{ $insurance->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="type_id" label="Tipo" placeholder="Selecione...">
                    @foreach ($this->types as $type)
                        <flux:select.option :value="$type->id">{{ $type->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="status_id" label="Status" placeholder="Selecione...">
                    @foreach ($this->statuses as $status)
                        <flux:select.option :value="$status->id">{{ $status->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="start_at" type="datetime-local" label="Início"/>
                <flux:input wire:model="end_at" type="datetime-local" label="Término"/>
            </div>

            <flux:textarea wire:model="notes" label="Observações" placeholder="Detalhes adicionais..."/>

            <div class="flex">
                <flux:spacer/>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:toast position="top center"/>
</div>
