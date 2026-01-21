<?php

use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\HealthInsurance;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Planos de saúde')] class extends Component {
    public ?HealthInsurance $editing = null;
    public string $name = '';

    #[Computed]
    public function healthInsurances()
    {
        return HealthInsurance::latest()->get();
    }

    public function create()
    {
        $this->editing = null;
        $this->name = '';

        $this->modal('health-insurance-form')->show();
    }

    public function edit(HealthInsurance $insurance)
    {
        $this->editing = $insurance;
        $this->name = $insurance->name;

        $this->modal('health-insurance-form')->show();
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
        ]);

        if ($this->editing) {
            $this->editing->update($validated);
            Flux::toast(
                text: 'Plano de saúde atualizado com sucesso.',
                heading: 'Registro atualizado',
                variant: 'success'
            );
        } else {
            HealthInsurance::create($validated);
            Flux::toast(
                text: 'Plano de saúde criado com sucesso.',
                heading: 'Registro criado',
                variant: 'success'
            );
        }

        $this->modal('health-insurance-form')->close();
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Planos de saúde</flux:heading>
    <flux:text class="mb-6 mt-2 text-base">Gerencie planos de saúde de maneira intuitiva</flux:text>
    <flux:separator variant="subtle" />

    <div class="mt-6">
        <flux:button variant="primary" icon="plus" wire:click="create">Novo plano de saúde</flux:button>
    </div>

    <div class="mt-8">
        @if ($this->healthInsurances->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-12 dark:border-zinc-700">
                <flux:icon.rectangle-stack class="mb-4 size-10 text-zinc-400" />
                <flux:heading>Nenhum plano cadastrado</flux:heading>
                <flux:text>Comece cadastrando um novo plano de saúde acima.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nome</flux:table.column>
                    <flux:table.column>Cadastrado em</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->healthInsurances as $insurance)
                        <flux:table.row :key="$insurance->id">
                            <flux:table.cell class="font-medium">{{ $insurance->name }}</flux:table.cell>
                            <flux:table.cell>{{ $insurance->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:.table.cell>
                                <flux:button variant="ghost" icon="pencil-square" size="sm" inset="top bottom" wire:click="edit({{ $insurance->id }})" />
                            </flux:.table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="health-insurance-form" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Editar plano de saúde' : 'Novo plano de saúde' }}</flux:heading>
                <flux:subheading>{{ $editing ? 'Atualize os dados do plano.' : 'Preencha os dados para cadastrar um novo plano.' }}</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Nome" placeholder="Ex: Unimed" />

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
