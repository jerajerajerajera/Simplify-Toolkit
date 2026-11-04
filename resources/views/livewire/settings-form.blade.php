{{-- No <form>: in Simplify settings › Add-ons this sits inside Simplify's own form. data-simplify-unsaved shows "Changes made"
     and asks before leaving; data-simplify-save lets Simplify's Save button save it (ADDONS.md, "Settings"). --}}
<div data-simplify-unsaved="data" data-simplify-save="save">
    {{ $this->form }}

    <x-filament-actions::modals />
</div>
