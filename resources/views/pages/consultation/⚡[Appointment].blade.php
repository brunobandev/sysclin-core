<?php

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\ChronicMedication;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordPhoto;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Consulta')] class extends Component {
    use WithFileUploads;

    public Appointment $appointment;

    // Medical record form fields
    public string $reason = '';
    public string $disease_cid = '';
    public string $subjective = '';
    public string $objective = '';
    public string $exams = '';
    public string $impression = '';
    public string $conduct = '';
    public string $description = '';
    public array $photos = [];

    public function mount(Appointment $appointment): void
    {
        Gate::authorize('start-consultation', $appointment);

        $this->appointment = $appointment->load('patient', 'medicalRecord');

        if ($record = $appointment->medicalRecord) {
            $this->reason = $record->reason ?? '';
            $this->disease_cid = $record->disease_cid ?? '';
            $this->subjective = $record->subjective ?? '';
            $this->objective = $record->objective ?? '';
            $this->exams = $record->exams ?? '';
            $this->impression = $record->impression ?? '';
            $this->conduct = $record->conduct ?? '';
            $this->description = $record->description ?? '';
        }
    }

    #[Computed]
    public function patient()
    {
        return $this->appointment->patient;
    }

    #[Computed]
    public function patientAge(): int
    {
        return Carbon::parse($this->patient->dob)->age;
    }

    #[Computed]
    public function previousMedicalRecords(): Collection
    {
        return $this->patient->medicalRecords()
            ->with('user')
            ->where('id', '!=', $this->appointment->medicalRecord?->id)
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function previousPrescriptions(): Collection
    {
        return $this->patient->prescriptions()
            ->with('user', 'items')
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function chronicMedication(): ?ChronicMedication
    {
        return $this->patient->chronicMedication;
    }

    public function startSession(): void
    {
        $status = AppointmentStatus::where('name', 'Em atendimento')->first();

        $this->appointment->update([
            'session_started_at' => now(),
            'appointment_status_id' => $status?->id,
        ]);
        $this->appointment->refresh();
    }

    public function saveMedicalRecord(): void
    {
        $validated = $this->validate([
            'reason' => 'nullable|string|max:255',
            'disease_cid' => 'nullable|string|max:20',
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'exams' => 'nullable|string',
            'impression' => 'nullable|string',
            'conduct' => 'nullable|string',
            'description' => 'nullable|string',
            'photos.*' => 'nullable|image|max:10240',
        ]);

        unset($validated['photos']);

        $record = MedicalRecord::updateOrCreate(
            ['appointment_id' => $this->appointment->id],
            [
                'patient_id' => $this->appointment->patient_id,
                'user_id' => auth()->id(),
                ...$validated,
            ]
        );

        foreach ($this->photos as $photo) {
            $path = $photo->store('medical-record-photos', 'private');

            $record->photos()->create([
                'path' => $path,
                'original_name' => $photo->getClientOriginalName(),
            ]);
        }

        $this->photos = [];
        $this->appointment->refresh();

        Flux::toast(text: 'Prontuário salvo com sucesso.', variant: 'success');
    }

    public function deletePhoto(int $photoId): void
    {
        $photo = MedicalRecordPhoto::findOrFail($photoId);

        Storage::disk('private')->delete($photo->path);
        $photo->delete();

        $this->appointment->refresh();
    }

    public function removeUploadedPhoto(int $index): void
    {
        if (isset($this->photos[$index])) {
            unset($this->photos[$index]);
            $this->photos = array_values($this->photos);
        }
    }

    public function endSession(): void
    {
        $status = AppointmentStatus::where('name', 'Realizada')->first();

        $this->appointment->update([
            'session_ended_at' => now(),
            'appointment_status_id' => $status?->id,
        ]);

        Flux::toast(text: 'Consulta finalizada.', variant: 'success');

        $this->redirect(route('appointment.list'));
    }
};
?>

<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">Consulta</flux:heading>
            <flux:text class="mt-1">{{ $this->patient->name }}</flux:text>
        </div>

        <div class="flex items-center gap-4"
             x-data="{
                startTime: @js($appointment->session_started_at?->timestamp),
                elapsed: 0,
                interval: null,
                init() {
                    if (this.startTime) {
                        this.updateElapsed();
                        this.interval = setInterval(() => this.updateElapsed(), 1000);
                    }
                    Livewire.on('sessionStarted', () => {
                        this.startTime = Math.floor(Date.now() / 1000);
                        this.updateElapsed();
                        this.interval = setInterval(() => this.updateElapsed(), 1000);
                    });
                },
                updateElapsed() {
                    this.elapsed = Math.floor(Date.now() / 1000) - this.startTime;
                },
                formatTime() {
                    const h = Math.floor(this.elapsed / 3600);
                    const m = Math.floor((this.elapsed % 3600) / 60);
                    const s = this.elapsed % 60;
                    return h + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                }
             }">
            @if ($appointment->session_started_at && !$appointment->session_ended_at)
                <div class="flex items-center gap-2 rounded-lg bg-green-50 px-4 py-2 dark:bg-green-900/20">
                    <div class="size-2 animate-pulse rounded-full bg-green-500"></div>
                    <span class="font-mono text-lg font-semibold text-green-700 dark:text-green-400" x-text="formatTime()"></span>
                </div>
                <flux:button variant="danger" icon="stop" wire:click="endSession" wire:confirm="Deseja finalizar a consulta?">
                    Finalizar
                </flux:button>
            @elseif (!$appointment->session_started_at)
                <flux:button variant="primary" icon="play" wire:click="startSession" x-on:click="$dispatch('sessionStarted')">
                    Iniciar Consulta
                </flux:button>
            @else
                <flux:badge color="zinc">Consulta finalizada</flux:badge>
            @endif

            <flux:button variant="ghost" icon="arrow-left" href="{{ route('appointment.list') }}">
                Voltar
            </flux:button>
        </div>
    </div>

    <flux:separator variant="subtle" />

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <flux:card>
                <flux:heading size="sm" class="mb-4">Dados do Paciente</flux:heading>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">Nome</dt>
                        <dd class="font-medium">{{ $this->patient->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">Idade</dt>
                        <dd class="font-medium">{{ $this->patientAge }} anos</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">Nascimento</dt>
                        <dd class="font-medium">{{ Carbon::parse($this->patient->dob)->format('d/m/Y') }}</dd>
                    </div>
                    @if ($this->patient->gender)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Gênero</dt>
                            <dd class="font-medium">{{ $this->patient->gender }}</dd>
                        </div>
                    @endif
                    @if ($this->patient->phone)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Telefone</dt>
                            <dd class="font-medium">{{ $this->patient->phone }}</dd>
                        </div>
                    @endif
                </dl>
            </flux:card>

            @if ($this->chronicMedication)
                <flux:card>
                    <flux:heading size="sm" class="mb-4">Medicações Crônicas</flux:heading>
                    <flux:text class="whitespace-pre-wrap text-sm">{{ $this->chronicMedication->medications }}</flux:text>
                </flux:card>
            @endif
        </div>

        <div class="lg:col-span-2">
            <flux:card>
                <flux:heading size="sm" class="mb-4">Prontuário</flux:heading>

                <form wire:submit="saveMedicalRecord" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input wire:model="reason" label="Motivo da consulta" placeholder="Ex: Dor abdominal" />
                        <flux:input wire:model="disease_cid" label="CID" placeholder="Ex: J11.1" />
                    </div>

                    <flux:textarea wire:model="subjective" label="Subjetivo" placeholder="Queixas e histórico do paciente..." rows="2" />
                    <flux:textarea wire:model="objective" label="Objetivo" placeholder="Achados do exame físico..." rows="2" />
                    <flux:textarea wire:model="exams" label="Exames" placeholder="Resultados de exames..." rows="2" />
                    <flux:textarea wire:model="impression" label="Impressão" placeholder="Hipótese diagnóstica..." rows="2" />
                    <flux:textarea wire:model="conduct" label="Conduta" placeholder="Plano terapêutico..." rows="2" />
                    <flux:textarea wire:model="description" label="Descrição" placeholder="Observações adicionais..." rows="2" />

                    @if ($appointment->medicalRecord && $appointment->medicalRecord->photos->count() > 0)
                        <div>
                            <flux:heading size="sm" class="mb-2">Fotos existentes</flux:heading>
                            <div class="flex flex-col gap-2">
                                @foreach ($appointment->medicalRecord->photos as $photo)
                                    <flux:file-item
                                        :heading="$photo->original_name"
                                        icon="photo"
                                    >
                                        <x-slot name="actions">
                                            <flux:file-item.remove wire:click="deletePhoto({{ $photo->id }})" wire:confirm="Remover esta foto?" />
                                        </x-slot>
                                    </flux:file-item>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <flux:file-upload wire:model="photos" multiple label="Fotos">
                        <flux:file-upload.dropzone heading="Arraste arquivos ou clique" text="JPG, PNG até 10MB" />
                    </flux:file-upload>

                    <div class="flex flex-col gap-2">
                        @foreach ($photos as $index => $photo)
                            <flux:file-item
                                :heading="$photo->getClientOriginalName()"
                                :image="$photo->temporaryUrl()"
                                :size="$photo->getSize()"
                            >
                                <x-slot name="actions">
                                    <flux:file-item.remove wire:click="removeUploadedPhoto({{ $index }})" />
                                </x-slot>
                            </flux:file-item>
                        @endforeach
                    </div>

                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary" icon="check">Salvar Prontuário</flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    </div>

    <div class="mt-6">
        <flux:card>
            <flux:tabs variant="segmented">
                <flux:tab name="records">Prontuários anteriores</flux:tab>
                <flux:tab name="prescriptions">Receituários</flux:tab>
            </flux:tabs>

            <flux:tab.panel name="records" class="mt-4">
                @if ($this->previousMedicalRecords->isEmpty())
                    <flux:text class="text-center text-zinc-500">Nenhum prontuário anterior encontrado.</flux:text>
                @else
                    <flux:accordion>
                        @foreach ($this->previousMedicalRecords as $record)
                            <flux:accordion.item :heading="$record->created_at->format('d/m/Y H:i') . ' - ' . ($record->reason ?? 'Sem motivo')">
                                <dl class="space-y-2 text-sm">
                                    <div>
                                        <dt class="font-medium text-zinc-500">Médico</dt>
                                        <dd>{{ $record->user->name }}</dd>
                                    </div>
                                    @if ($record->disease_cid)
                                        <div>
                                            <dt class="font-medium text-zinc-500">CID</dt>
                                            <dd>{{ $record->disease_cid }}</dd>
                                        </div>
                                    @endif
                                    @if ($record->subjective)
                                        <div>
                                            <dt class="font-medium text-zinc-500">Subjetivo</dt>
                                            <dd class="whitespace-pre-wrap">{{ $record->subjective }}</dd>
                                        </div>
                                    @endif
                                    @if ($record->objective)
                                        <div>
                                            <dt class="font-medium text-zinc-500">Objetivo</dt>
                                            <dd class="whitespace-pre-wrap">{{ $record->objective }}</dd>
                                        </div>
                                    @endif
                                    @if ($record->impression)
                                        <div>
                                            <dt class="font-medium text-zinc-500">Impressão</dt>
                                            <dd class="whitespace-pre-wrap">{{ $record->impression }}</dd>
                                        </div>
                                    @endif
                                    @if ($record->conduct)
                                        <div>
                                            <dt class="font-medium text-zinc-500">Conduta</dt>
                                            <dd class="whitespace-pre-wrap">{{ $record->conduct }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </flux:accordion.item>
                        @endforeach
                    </flux:accordion>
                @endif
            </flux:tab.panel>

            <flux:tab.panel name="prescriptions" class="mt-4">
                @if ($this->previousPrescriptions->isEmpty())
                    <flux:text class="text-center text-zinc-500">Nenhum receituário encontrado.</flux:text>
                @else
                    <flux:accordion>
                        @foreach ($this->previousPrescriptions as $prescription)
                            <flux:accordion.item :heading="$prescription->created_at->format('d/m/Y H:i') . ' - ' . $prescription->type->label()">
                                <dl class="space-y-2 text-sm">
                                    <div>
                                        <dt class="font-medium text-zinc-500">Médico</dt>
                                        <dd>{{ $prescription->user->name }}</dd>
                                    </div>
                                    @if ($prescription->items->isNotEmpty())
                                        <div>
                                            <dt class="font-medium text-zinc-500">Medicamentos</dt>
                                            <dd>
                                                <ul class="mt-1 list-inside list-disc">
                                                    @foreach ($prescription->items as $item)
                                                        <li>{{ $item->medication }} - {{ $item->quantity }} ({{ $item->frequency }})</li>
                                                    @endforeach
                                                </ul>
                                            </dd>
                                        </div>
                                    @endif
                                    @if ($prescription->notes)
                                        <div>
                                            <dt class="font-medium text-zinc-500">Observações</dt>
                                            <dd class="whitespace-pre-wrap">{{ $prescription->notes }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </flux:accordion.item>
                        @endforeach
                    </flux:accordion>
                @endif
            </flux:tab.panel>
        </flux:card>
    </div>

    <flux:toast position="top center" />
</div>
