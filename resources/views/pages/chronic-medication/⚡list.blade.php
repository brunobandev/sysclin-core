<?php

use App\Models\ChronicMedication;
use App\Models\Patient;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Medicações Crônicas')] class extends Component {
    public ?Patient $selectedPatient = null;
    public string $medications = '';
    public string $search = '';

    #[Computed]
    public function patients()
    {
        return Patient::with('chronicMedication')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->get();
    }

    public function editMedications(Patient $patient): void
    {
        $this->selectedPatient = $patient;
        $this->medications = $patient->chronicMedication?->medications ?? '';

        $this->modal('chronic-medication-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'medications' => 'required|string',
        ]);

        ChronicMedication::updateOrCreate(
            ['patient_id' => $this->selectedPatient->id],
            ['medications' => $validated['medications']],
        );

        Flux::toast(text: 'Medicações crônicas atualizadas com sucesso.', heading: 'Registro atualizado', variant: 'success');
        $this->modal('chronic-medication-form')->close();
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Medicações Crônicas</flux:heading>
    <flux:text class="mb-6 mt-2 text-base">Gerencie as medicações crônicas dos pacientes</flux:text>
    <flux:separator variant="subtle" />

    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por paciente..." icon="magnifying-glass" class="max-w-xs" />
    </div>

    <div class="mt-8">
        @if ($this->patients->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-12 dark:border-zinc-700">
                <flux:icon.users class="mb-4 size-10 text-zinc-400" />
                <flux:heading>Nenhum paciente encontrado</flux:heading>
                <flux:text>Cadastre pacientes para gerenciar suas medicações crônicas.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Paciente</flux:table.column>
                    <flux:table.column>Medicações</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->patients as $patient)
                        <flux:table.row :key="$patient->id">
                            <flux:table.cell class="font-medium">{{ $patient->name }}</flux:table.cell>
                            <flux:table.cell class="max-w-md truncate">
                                {{ $patient->chronicMedication?->medications ?? '-' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button variant="ghost" icon="pencil-square" size="sm" inset="top bottom" wire:click="editMedications({{ $patient->id }})" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="chronic-medication-form" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Medicações Crônicas</flux:heading>
                <flux:subheading>{{ $selectedPatient?->name }}</flux:subheading>
            </div>

            <flux:textarea wire:model="medications" label="Medicações" placeholder="Liste as medicações crônicas do paciente..." rows="8" />

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
