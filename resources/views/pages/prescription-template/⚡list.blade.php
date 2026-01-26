<?php

use App\Models\PrescriptionTemplate;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Modelos de Receituário')] class extends Component {
    public ?PrescriptionTemplate $editing = null;
    public string $name = '';
    public array $items = [];

    public function mount(): void
    {
        $this->authorize('manage-prescription-templates');
    }

    #[Computed]
    public function templates()
    {
        return PrescriptionTemplate::with('items')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();
    }

    public function create(): void
    {
        $this->resetFields();
        $this->addItem();
        $this->modal('template-form')->show();
    }

    public function edit(PrescriptionTemplate $template): void
    {
        $this->editing = $template->load('items');
        $this->name = $template->name;

        $this->items = $template->items->map(fn ($item) => [
            'medication' => $item->medication,
            'quantity' => $item->quantity ?? '',
            'frequency' => $item->frequency ?? '',
            'usage_type' => $item->usage_type ?? '',
        ])->toArray();

        if (empty($this->items)) {
            $this->addItem();
        }

        $this->modal('template-form')->show();
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
            'name' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.medication' => 'required|string|max:255',
            'items.*.quantity' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.usage_type' => 'nullable|string|max:255',
        ]);

        if ($this->editing) {
            $this->editing->update(['name' => $validated['name']]);
            $template = $this->editing;
            $template->items()->delete();
            $toastText = 'Modelo atualizado com sucesso.';
            $toastHeading = 'Registro atualizado';
        } else {
            $template = PrescriptionTemplate::create([
                'user_id' => auth()->id(),
                'name' => $validated['name'],
            ]);
            $toastText = 'Modelo criado com sucesso.';
            $toastHeading = 'Registro criado';
        }

        foreach ($validated['items'] as $item) {
            $template->items()->create($item);
        }

        Flux::toast(text: $toastText, heading: $toastHeading, variant: 'success');
        $this->modal('template-form')->close();
    }

    public function delete(PrescriptionTemplate $template): void
    {
        $template->delete();

        Flux::toast(text: 'Modelo removido com sucesso.', heading: 'Registro removido', variant: 'success');
    }

    private function resetFields(): void
    {
        $this->editing = null;
        $this->name = '';
        $this->items = [];
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Modelos de Receituário</flux:heading>
    <flux:text class="mb-6 mt-2 text-base">Gerencie modelos de receituário para agilizar o atendimento</flux:text>
    <flux:separator variant="subtle" />

    <div class="mt-6">
        <flux:button variant="primary" icon="plus" wire:click="create">Novo modelo</flux:button>
    </div>

    <div class="mt-8">
        @if ($this->templates->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-12 dark:border-zinc-700">
                <flux:icon.document-duplicate class="mb-4 size-10 text-zinc-400" />
                <flux:heading>Nenhum modelo cadastrado</flux:heading>
                <flux:text>Comece cadastrando um novo modelo acima.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nome</flux:table.column>
                    <flux:table.column>Itens</flux:table.column>
                    <flux:table.column>Criado em</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->templates as $template)
                        <flux:table.row :key="$template->id">
                            <flux:table.cell class="font-medium">{{ $template->name }}</flux:table.cell>
                            <flux:table.cell>{{ $template->items->count() }}</flux:table.cell>
                            <flux:table.cell>{{ $template->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-1">
                                    <flux:button variant="ghost" icon="pencil-square" size="sm" inset="top bottom" wire:click="edit({{ $template->id }})" />
                                    <flux:button variant="ghost" icon="trash" size="sm" inset="top bottom" wire:click="delete({{ $template->id }})" wire:confirm="Tem certeza que deseja remover este modelo?" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="template-form" class="w-full md:max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Editar modelo' : 'Novo modelo' }}</flux:heading>
                <flux:subheading>{{ $editing ? 'Atualize o modelo de receituário.' : 'Crie um modelo para reutilizar em receituários.' }}</flux:subheading>
            </div>

            <div class="space-y-6">
                <flux:input wire:model="name" label="Nome do modelo" placeholder="Ex: Gripe comum" />

                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="sm">Medicamentos</flux:heading>
                        <flux:button variant="ghost" icon="plus" size="sm" wire:click="addItem" type="button">Adicionar</flux:button>
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
