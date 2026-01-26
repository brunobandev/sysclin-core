<?php

use App\Models\MedicalRecord;
use App\Models\MedicalRecordPhoto;
use App\Models\Patient;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Prontuários')] class extends Component {
    use WithFileUploads;

    public ?MedicalRecord $editing = null;
    public string $patient_id = '';
    public string $reason = '';
    public string $disease_cid = '';
    public string $subjective = '';
    public string $objective = '';
    public string $exams = '';
    public string $impression = '';
    public string $conduct = '';
    public string $description = '';
    public array $photos = [];
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('manage-medical-records');
    }

    #[Computed]
    public function patients()
    {
        return Patient::orderBy('name')->get();
    }

    #[Computed]
    public function medicalRecords()
    {
        return MedicalRecord::with('patient', 'user', 'photos')
            ->when($this->search, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->get();
    }

    public function create(): void
    {
        $this->resetFields();
        $this->modal('medical-record-form')->show();
    }

    public function edit(MedicalRecord $medicalRecord): void
    {
        $this->editing = $medicalRecord;
        $this->patient_id = (string) $medicalRecord->patient_id;
        $this->reason = $medicalRecord->reason ?? '';
        $this->disease_cid = $medicalRecord->disease_cid ?? '';
        $this->subjective = $medicalRecord->subjective ?? '';
        $this->objective = $medicalRecord->objective ?? '';
        $this->exams = $medicalRecord->exams ?? '';
        $this->impression = $medicalRecord->impression ?? '';
        $this->conduct = $medicalRecord->conduct ?? '';
        $this->description = $medicalRecord->description ?? '';
        $this->photos = [];

        $this->modal('medical-record-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'patient_id' => 'required|exists:patients,id',
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
        $validated['user_id'] = auth()->id();

        if ($this->editing) {
            $this->editing->update($validated);
            $record = $this->editing;
            $toastText = 'Prontuário atualizado com sucesso.';
            $toastHeading = 'Registro atualizado';
        } else {
            $record = MedicalRecord::create($validated);
            $toastText = 'Prontuário criado com sucesso.';
            $toastHeading = 'Registro criado';
        }

        foreach ($this->photos as $photo) {
            $path = $photo->store('medical-record-photos', 'private');

            $record->photos()->create([
                'path' => $path,
                'original_name' => $photo->getClientOriginalName(),
            ]);
        }

        Flux::toast(text: $toastText, heading: $toastHeading, variant: 'success');
        $this->modal('medical-record-form')->close();
    }

    public function deletePhoto(int $photoId): void
    {
        $photo = MedicalRecordPhoto::findOrFail($photoId);

        Storage::disk('private')->delete($photo->path);
        $photo->delete();
    }

    public function removeUploadedPhoto(int $index): void
    {
        if (isset($this->photos[$index])) {
            unset($this->photos[$index]);
            $this->photos = array_values($this->photos);
        }
    }

    public function delete(MedicalRecord $medicalRecord): void
    {
        foreach ($medicalRecord->photos as $photo) {
            Storage::disk('private')->delete($photo->path);
        }

        $medicalRecord->delete();

        Flux::toast(text: 'Prontuário removido com sucesso.', heading: 'Registro removido', variant: 'success');
    }

    private function resetFields(): void
    {
        $this->editing = null;
        $this->patient_id = '';
        $this->reason = '';
        $this->disease_cid = '';
        $this->subjective = '';
        $this->objective = '';
        $this->exams = '';
        $this->impression = '';
        $this->conduct = '';
        $this->description = '';
        $this->photos = [];
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Prontuários</flux:heading>
    <flux:text class="mb-6 mt-2 text-base">Gerencie os prontuários médicos dos pacientes</flux:text>
    <flux:separator variant="subtle" />

    <div class="mt-6 flex items-center gap-4">
        <flux:button variant="primary" icon="plus" wire:click="create">Novo prontuário</flux:button>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por paciente..." icon="magnifying-glass" class="max-w-xs" />
    </div>

    <div class="mt-8">
        @if ($this->medicalRecords->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-12 dark:border-zinc-700">
                <flux:icon.clipboard-document-list class="mb-4 size-10 text-zinc-400" />
                <flux:heading>Nenhum prontuário cadastrado</flux:heading>
                <flux:text>Comece cadastrando um novo prontuário acima.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Paciente</flux:table.column>
                    <flux:table.column>Motivo</flux:table.column>
                    <flux:table.column>CID</flux:table.column>
                    <flux:table.column>Médico</flux:table.column>
                    <flux:table.column>Fotos</flux:table.column>
                    <flux:table.column>Data</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->medicalRecords as $record)
                        <flux:table.row :key="$record->id">
                            <flux:table.cell class="font-medium">{{ $record->patient->name }}</flux:table.cell>
                            <flux:table.cell>{{ $record->reason ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $record->disease_cid ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $record->user->name }}</flux:table.cell>
                            <flux:table.cell>{{ $record->photos->count() }}</flux:table.cell>
                            <flux:table.cell>{{ $record->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-1">
                                    <flux:button variant="ghost" icon="pencil-square" size="sm" inset="top bottom" wire:click="edit({{ $record->id }})" />
                                    <flux:button variant="ghost" icon="trash" size="sm" inset="top bottom" wire:click="delete({{ $record->id }})" wire:confirm="Tem certeza que deseja remover este prontuário?" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="medical-record-form" class="w-full md:max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Editar prontuário' : 'Novo prontuário' }}</flux:heading>
                <flux:subheading>{{ $editing ? 'Atualize os dados do prontuário.' : 'Preencha os dados para registrar um novo prontuário.' }}</flux:subheading>
            </div>

            <div class="space-y-6">
                <flux:select wire:model="patient_id" label="Paciente" placeholder="Selecione o paciente...">
                    @foreach ($this->patients as $patient)
                        <flux:select.option :value="$patient->id">{{ $patient->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <flux:input wire:model="reason" label="Motivo da consulta" placeholder="Ex: Dor abdominal" />
                    <flux:input wire:model="disease_cid" label="CID da doença" placeholder="Ex: J11.1" />
                </div>

                <flux:textarea wire:model="subjective" label="Subjetivo" placeholder="Queixas e histórico do paciente..." rows="3" />
                <flux:textarea wire:model="objective" label="Objetivo" placeholder="Achados do exame físico..." rows="3" />
                <flux:textarea wire:model="exams" label="Exames" placeholder="Resultados de exames..." rows="3" />
                <flux:textarea wire:model="impression" label="Impressão" placeholder="Hipótese diagnóstica..." rows="3" />
                <flux:textarea wire:model="conduct" label="Conduta" placeholder="Plano terapêutico..." rows="3" />
                <flux:textarea wire:model="description" label="Descrição" placeholder="Observações adicionais..." rows="3" />

                @if ($editing && $editing->photos->count() > 0)
                    <div>
                        <flux:heading size="sm" class="mb-2">Fotos existentes</flux:heading>
                        <div class="flex flex-col gap-2">
                            @foreach ($editing->photos as $photo)
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
                    <flux:file-upload.dropzone heading="Arraste arquivos ou clique para selecionar" text="JPG, PNG até 10MB" />
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
            </div>

            <div class="flex">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" class="ml-2">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:toast position="top center" />
</div>
