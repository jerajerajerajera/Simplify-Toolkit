<x-filament-panels::page>
    {{-- s-fit: with Simplify installed the admin page never scrolls, only this form does (ADDONS.md). The server pages fit already. --}}
    {{-- data-simplify-unsaved: Simplify shows "Changes made" with Discard and Save, and asks before leaving (ADDONS.md, "Unsaved changes"). --}}
    <form wire:submit="save" data-simplify-unsaved="data" @class(['s-fit' => filament()->getCurrentPanel()?->getId() === 'admin'])>
        {{ $this->form }}
    </form>
</x-filament-panels::page>
