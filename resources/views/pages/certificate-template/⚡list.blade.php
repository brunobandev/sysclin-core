<?php

use App\Models\CertificateTemplate;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Modelos de Atestado')] class extends Component {
    public ?CertificateTemplate $editing = null;
    public string $name = '';
    public string $content = '';
    public string $cid = '';

    public function mount(): void
    {
        $this->authorize('manage-certificate-templates');
    }

    #[Computed]
    public function templates()
    {
        return CertificateTemplate::latest()->get();
    }

    public function create(): void
    {
        $this->editing = null;
        $this->name = '';
        $this->content = '';
        $this->cid = '';

        $this->modal('template-form')->show();
    }

    public function edit(CertificateTemplate $template): void
    {
        $this->editing = $template;
        $this->name = $template->name;
        $this->content = $template->content;
        $this->cid = $template->cid ?? '';

        $this->modal('template-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'cid' => 'nullable|string|max:20',
        ]);

        if ($this->editing) {
            $this->editing->update($validated);
            Flux::toast(text: 'Modelo atualizado com sucesso.', heading: 'Registro atualizado', variant: 'success');
        } else {
            CertificateTemplate::create($validated);
            Flux::toast(text: 'Modelo criado com sucesso.', heading: 'Registro criado', variant: 'success');
        }

        $this->modal('template-form')->close();
    }

    public function delete(CertificateTemplate $template): void
    {
        $template->delete();

        Flux::toast(text: 'Modelo removido com sucesso.', heading: 'Registro removido', variant: 'success');
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Modelos de Atestado</flux:heading>
    <flux:text class="mb-6 mt-2 text-base">Gerencie modelos de atestado médico</flux:text>
    <flux:separator variant="subtle" />

    <div class="mt-6">
        <flux:button variant="primary" icon="plus" wire:click="create">Novo modelo</flux:button>
    </div>

    <div class="mt-8">
        @if ($this->templates->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-12 dark:border-zinc-700">
                <flux:icon.document-check class="mb-4 size-10 text-zinc-400" />
                <flux:heading>Nenhum modelo cadastrado</flux:heading>
                <flux:text>Comece cadastrando um novo modelo acima.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nome</flux:table.column>
                    <flux:table.column>CID</flux:table.column>
                    <flux:table.column>Criado em</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->templates as $template)
                        <flux:table.row :key="$template->id">
                            <flux:table.cell class="font-medium">{{ $template->name }}</flux:table.cell>
                            <flux:table.cell>{{ $template->cid ?? '-' }}</flux:table.cell>
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

    <flux:modal name="template-form" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Editar modelo' : 'Novo modelo' }}</flux:heading>
                <flux:subheading>{{ $editing ? 'Atualize o modelo de atestado.' : 'Crie um modelo de atestado para reutilizar.' }}</flux:subheading>
            </div>

            <div class="space-y-6">
                <flux:input wire:model="name" label="Nome do modelo" placeholder="Ex: Atestado de comparecimento" />
                <flux:textarea wire:model="content" label="Conteúdo" placeholder="Texto do atestado..." rows="6" />
                <flux:input wire:model="cid" label="CID (opcional)" placeholder="Ex: J11.1" />
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
