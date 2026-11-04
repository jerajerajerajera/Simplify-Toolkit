{{-- One flat row on Simplify's Connections page, like Server Maker's Steam row: icon, what is connected, the add-on it is for, and its buttons (Remove in the ⋯ menu). --}}
<div class="tk-connection">
    <span class="tk-connection-icon"><x-filament::icon icon="tabler-flame" /></span>
    <div class="tk-connection-text">
        <strong>CurseForge</strong>
        <span>{{ $line }}</span>
    </div>
    <span class="tk-connection-addon">Toolkit</span>
    {{ $this->connectAction }}
    @if ($saved)
        <x-filament-actions::group :actions="[$this->disconnectAction]" icon="tabler-dots" color="gray" />
    @endif

    <x-filament-actions::modals />
    <style>
        .tk-connection { display: flex; align-items: center; gap: 16px; padding: 14px 0; }
        .tk-connection-icon { display: grid; place-items: center; flex: none; width: 40px; height: 40px; border-radius: 10px; background: var(--ps-raised, var(--gray-100)); }
        .dark .tk-connection-icon { background: var(--ps-raised, var(--gray-800)); }
        .tk-connection-icon svg { width: 22px; height: 22px; }
        .tk-connection-text { display: flex; flex: 1; min-width: 0; flex-direction: column; gap: 3px; font-size: 13px; color: var(--ps-muted, var(--gray-500)); }
        .tk-connection-text strong { font-size: 15px; color: var(--ps-ink, var(--gray-950)); }
        .dark .tk-connection-text strong { color: var(--ps-ink, #fff); }
        .tk-connection-addon { font-size: 12px; color: var(--ps-muted, var(--gray-500)); }
        @media (max-width: 600px) { .tk-connection { flex-wrap: wrap; } .tk-connection-text { flex-basis: calc(100% - 56px); } .tk-connection-addon { flex: 1; } }
    </style>
</div>
