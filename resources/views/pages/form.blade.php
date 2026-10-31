<x-filament-panels::page>
    {{-- s-fit: with Simplify installed the admin page never scrolls, only this form does (ADDONS.md). The server pages fit already. --}}
    <form wire:submit="save" @class(['s-fit' => filament()->getCurrentPanel()?->getId() === 'admin'])>
        {{ $this->form }}
    </form>
</x-filament-panels::page>
