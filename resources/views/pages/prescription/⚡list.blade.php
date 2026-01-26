<?php

use App\Enums\PrescriptionType;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionTemplate;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Receituários')] class extends Component {
    public ?Prescription $editing = null;
    public string $patient_id = '';
    public string $type = '';
    public string $usage_type = '';
    public string $disease_cid = '';
    public string $notes = '';
    public array $items = [];
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('manage-prescriptions');
    }

    #[Computed]
    public function patients()
    {
        return Patient::orderBy('name')->get();
    }

    #[Computed]
    public function prescriptions()
    {
        return Prescription::with('patient', 'user', 'items')
            ->when($this->search, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->get();
    }

    #[Computed]
    public function prescriptionTypes()
    {
        return PrescriptionType::cases();
    }

    #[Computed]
    public function templates()
    {
        return PrescriptionTemplate::with('items')
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();
    }

    public function loadTemplate(int $templateId): void
    {
        $template = PrescriptionTemplate::with('items')->findOrFail($templateId);

        $this->items = $template->items->map(fn ($item) => [
            'medication' => $item->medication,
            'quantity' => $item->quantity ?? '',
            'frequency' => $item->frequency ?? '',
            'usage_type' => $item->usage_type ?? '',
        ])->toArray();
    }

    public function create(): void
    {
        $this->resetFields();
        $this->addItem();
        $this->modal('prescription-form')->show();
    }

    public function edit(Prescription $prescription): void
    {
        $this->editing = $prescription->load('items');
        $this->patient_id = (string) $prescription->patient_id;
        $this->type = $prescription->type->value;
        $this->usage_type = $prescription->usage_type ?? '';
        $this->disease_cid = $prescription->disease_cid ?? '';
        $this->notes = $prescription->notes ?? '';

        $this->items = $prescription->items->map(fn ($item) => [
            'medication' => $item->medication,
            'quantity' => $item->quantity ?? '',
            'frequency' => $item->frequency ?? '',
            'usage_type' => $item->usage_type ?? '',
        ])->toArray();

        if (empty($this->items)) {
            $this->addItem();
        }

        $this->modal('prescription-form')->show();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'medication' => '',
            'quantity' => '',
            'frequency' => '',
            'usage_type' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'patient_id' => 'required|exists:patients,id',
            'type' => 'required|string',
            'usage_type' => 'nullable|string|max:255',
            'disease_cid' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.medication' => 'required|string|max:255',
            'items.*.quantity' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.usage_type' => 'nullable|string|max:255',
        ]);

        $prescriptionData = collect($validated)->except('items')->toArray();
        $prescriptionData['user_id'] = auth()->id();

        if ($this->editing) {
            $this->editing->update($prescriptionData);
            $prescription = $this->editing;
            $prescription->items()->delete();
            $toastText = 'Receituário atualizado com sucesso.';
            $toastHeading = 'Registro atualizado';
        } else {
            $prescription = Prescription::create($prescriptionData);
            $toastText = 'Receituário criado com sucesso.';
            $toastHeading = 'Registro criado';
        }

        foreach ($validated['items'] as $item) {
            $prescription->items()->create($item);
        }

        Flux::toast(text: $toastText, heading: $toastHeading, variant: 'success');
        $this->modal('prescription-form')->close();
    }

    public function delete(Prescription $prescription): void
    {
        $prescription->delete();

        Flux::toast(text: 'Receituário removido com sucesso.', heading: 'Registro removido', variant: 'success');
    }

    private function resetFields(): void
    {
        $this->editing = null;
        $this->patient_id = '';
        $this->type = '';
        $this->usage_type = '';
        $this->disease_cid = '';
        $this->notes = '';
        $this->items = [];
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Receituários</flux:heading>
    <flux:text class="mb-6 mt-2 text-base">Gerencie os receituários médicos dos pacientes</flux:text>
    <flux:separator variant="subtle" />

    <div class="mt-6 flex items-center gap-4">
        <flux:button variant="primary" icon="plus" wire:click="create">Novo receituário</flux:button>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por paciente..." icon="magnifying-glass" class="max-w-xs" />
    </div>

    <div class="mt-8">
        @if ($this->prescriptions->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-12 dark:border-zinc-700">
                <flux:icon.document-text class="mb-4 size-10 text-zinc-400" />
                <flux:heading>Nenhum receituário cadastrado</flux:heading>
                <flux:text>Comece cadastrando um novo receituário acima.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Paciente</flux:table.column>
                    <flux:table.column>Tipo</flux:table.column>
                    <flux:table.column>CID</flux:table.column>
                    <flux:table.column>Itens</flux:table.column>
                    <flux:table.column>Médico</flux:table.column>
                    <flux:table.column>Data</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->prescriptions as $prescription)
                        <flux:table.row :key="$prescription->id">
                            <flux:table.cell class="font-medium">{{ $prescription->patient->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :variant="$prescription->type === \App\Enums\PrescriptionType::ControleEspecial ? 'warning' : 'default'">
                                    {{ $prescription->type->label() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $prescription->disease_cid ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $prescription->items->count() }}</flux:table.cell>
                            <flux:table.cell>{{ $prescription->user->name }}</flux:table.cell>
                            <flux:table.cell>{{ $prescription->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-1">
                                    <flux:button variant="ghost" icon="pencil-square" size="sm" inset="top bottom" wire:click="edit({{ $prescription->id }})" />
                                    <flux:button variant="ghost" icon="trash" size="sm" inset="top bottom" wire:click="delete({{ $prescription->id }})" wire:confirm="Tem certeza que deseja remover este receituário?" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="prescription-form" class="w-full md:max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Editar receituário' : 'Novo receituário' }}</flux:heading>
                <flux:subheading>{{ $editing ? 'Atualize os dados do receituário.' : 'Preencha os dados para criar um novo receituário.' }}</flux:subheading>
            </div>

            <div class="space-y-6">
                <flux:select wire:model="patient_id" label="Paciente" placeholder="Selecione o paciente...">
                    @foreach ($this->patients as $patient)
                        <flux:select.option :value="$patient->id">{{ $patient->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <flux:select wire:model="type" label="Tipo" placeholder="Selecione...">
                        @foreach ($this->prescriptionTypes as $prescriptionType)
                            <flux:select.option :value="$prescriptionType->value">{{ $prescriptionType->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="disease_cid" label="CID da doença" placeholder="Ex: J11.1" />
                </div>

                <flux:input wire:model="usage_type" label="Tipo de uso" placeholder="Ex: Oral, Tópico" />
                <flux:textarea wire:model="notes" label="Observações" placeholder="Observações adicionais..." rows="2" />

                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="sm">Medicamentos</flux:heading>
                        <div class="flex gap-2">
                            @if ($this->templates->isNotEmpty())
                                <flux:dropdown>
                                    <flux:button variant="ghost" icon="document-duplicate" size="sm" type="button">Carregar modelo</flux:button>
                                    <flux:menu>
                                        @foreach ($this->templates as $template)
                                            <flux:menu.item wire:click="loadTemplate({{ $template->id }})">{{ $template->name }}</flux:menu.item>
                                        @endforeach
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                            <flux:button variant="ghost" icon="plus" size="sm" wire:click="addItem" type="button">Adicionar</flux:button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @foreach ($items as $index => $item)
                            <div wire:key="item-{{ $index }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="mb-3 flex items-center justify-between">
                                    <flux:text class="text-sm font-medium">Item {{ $index + 1 }}</flux:text>
                                    @if (count($items) > 1)
                                        <flux:button variant="ghost" icon="trash" size="xs" wire:click="removeItem({{ $index }})" type="button" />
                                    @endif
                                </div>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <flux:input wire:model="items.{{ $index }}.medication" label="Medicamento" placeholder="Nome do medicamento" />
                                    <flux:input wire:model="items.{{ $index }}.quantity" label="Quantidade" placeholder="Ex: 30 comprimidos" />
                                    <flux:input wire:model="items.{{ $index }}.frequency" label="Frequência" placeholder="Ex: 8/8h" />
                                    <flux:input wire:model="items.{{ $index }}.usage_type" label="Tipo de uso" placeholder="Ex: Oral" />
                                </div>
                            </div>
                        @endforeach
                    </div>
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
