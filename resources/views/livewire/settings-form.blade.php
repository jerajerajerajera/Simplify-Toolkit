{{-- No <form>: in Simplify settings › Add-ons this sits inside Simplify's own form. data-simplify-unsaved (the form, and the eggs' player counts) shows "Changes made"
     and asks before leaving; data-simplify-save lets Simplify's Save button save it (ADDONS.md, "Settings"). --}}
<div data-simplify-unsaved="data,eggCounts" data-simplify-save="save">
    {{ $this->form }}

    <x-filament-actions::modals />
</div>
