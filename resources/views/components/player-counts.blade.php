{{-- Simplify › Toolkit › Player counts: one row per egg with its protocol, query port and servers. Edit opens the
     egg's window; the change shows here at once and is stored with the form's Save. A search field narrows the rows. --}}
@php($rows = $this->playerCountRows())
<div class="tk-counts" x-data="{ q: '' }">
    @if (count($rows) > 8)
        <label class="tk-counts-search">
            <x-filament::icon icon="tabler-search" />
            <input type="search" x-model="q" placeholder="{{ __('simplify-toolkit::counts.search') }}" aria-label="{{ __('simplify-toolkit::counts.search') }}">
        </label>
    @endif
    <div class="tk-counts-scroll">
        <table>
            <thead>
                <tr>
                    <th scope="col">{{ __('simplify-toolkit::counts.egg') }}</th>
                    <th scope="col">{{ __('simplify-toolkit::counts.protocol') }}</th>
                    <th scope="col">{{ __('simplify-toolkit::counts.query_port') }}</th>
                    <th scope="col">{{ __('simplify-toolkit::counts.servers') }}</th>
                    <th scope="col"><span class="sr-only">{{ __('simplify-toolkit::counts.edit') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr wire:key="tk-count-{{ $row['id'] }}" x-show="q === '' || @js(mb_strtolower($row['name'])).includes(q.toLowerCase())">
                        <td class="tk-counts-name">{{ $row['name'] }}</td>
                        <td>
                            <span class="tk-counts-protocol"><span class="tk-counts-dot is-{{ $row['tone'] }}"></span>{{ $row['protocol'] }}</span>
                            @if ($row['note'] !== '')
                                <span class="tk-counts-note">{{ $row['note'] }}</span>
                            @endif
                        </td>
                        <td>{{ $row['port'] }}</td>
                        <td class="tk-counts-muted">{{ $row['servers'] }}</td>
                        <td class="tk-counts-edit">
                            @if ($row['from_player_counter'])
                                <span class="tk-counts-pc">{{ __('simplify-toolkit::counts.in_player_counter') }}</span>
                            @else
                                {{ ($this->editEggAction)(['egg' => $row['id']]) }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="tk-counts-muted">{{ __('simplify-toolkit::counts.no_eggs') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <style>
        .tk-counts { display: flex; flex-direction: column; gap: 10px; min-width: 0; }
        .tk-counts-search { display: flex; align-items: center; gap: 8px; align-self: flex-end; color: var(--ps-muted, var(--gray-500)); }
        .tk-counts-search svg { width: 18px; height: 18px; }
        .tk-counts-search input { height: 36px; width: 220px; max-width: 100%; box-sizing: border-box; padding: 0 10px; border-radius: 8px; border: 1px solid var(--ps-line, var(--gray-300)); background: transparent; color: inherit; font: inherit; font-size: .875rem; }
        .dark .tk-counts-search input { border-color: var(--ps-line, rgb(255 255 255 / .15)); }
        .tk-counts-scroll { overflow-x: auto; max-width: 100%; }
        .tk-counts table { width: 100%; min-width: 640px; border-collapse: collapse; font-size: .875rem; }
        .tk-counts th { text-align: left; font-size: .75rem; font-weight: 600; color: var(--ps-muted, var(--gray-500)); padding: 8px; border-bottom: 1px solid var(--ps-line, var(--gray-200)); }
        .tk-counts td { padding: 10px 8px; border-bottom: 1px solid var(--ps-line, var(--gray-200)); vertical-align: middle; color: var(--ps-ink, var(--gray-950)); }
        .dark .tk-counts th, .dark .tk-counts td { border-color: var(--ps-line, rgb(255 255 255 / .1)); }
        .dark .tk-counts td { color: var(--ps-ink, #fff); }
        .tk-counts-name { font-weight: 500; }
        .tk-counts-protocol { display: inline-flex; align-items: center; gap: 6px; }
        .tk-counts-dot { width: 8px; height: 8px; border-radius: 4px; background: var(--gray-400); flex: none; }
        .tk-counts-dot.is-success { background: var(--ps-ok, var(--success-500)); }
        .tk-counts-dot.is-info { background: var(--ps-accent, var(--primary-500)); }
        .tk-counts-dot.is-off { background: var(--gray-600); }
        .tk-counts-note { display: block; margin-top: 2px; font-size: .75rem; color: var(--ps-muted, var(--gray-500)); }
        .tk-counts td.tk-counts-muted { color: var(--ps-muted, var(--gray-500)); }
        .tk-counts-edit { text-align: right; white-space: nowrap; }
        .tk-counts-pc { font-size: .75rem; color: var(--ps-accent, var(--primary-500)); }
    </style>
</div>
