<x-filament-panels::page>
    @once
        <style>@include('simplify-toolkit::assets.pages-css')</style>
    @endonce
    @php
        $mods = $context['kind'] === 'mods';
        $sites = ['modrinth' => 'Modrinth', 'hangar' => 'Hangar', 'spigot' => 'SpigotMC', 'curseforge' => 'CurseForge'];
    @endphp

    @if ($unreachable)
        <p class="sm-note is-danger">{{ __('simplify-toolkit::packages.unreachable') }}</p>
    @endif

    <div class="sm-columns" wire:init="load">
        <x-filament::section :heading="__('simplify-toolkit::packages.find')" :description="$loaded && $this->ready() ? __('simplify-toolkit::packages.for', ['loader' => ucfirst($context['loader']), 'version' => $context['minecraft']]) : null">
            @if (! $loaded)
                {{-- The boxes stand at once; these rows hold the place of the results until load() is back. --}}
                <ul class="sm-list sm-skeleton" aria-busy="true">
                    @for ($row = 0; $row < 6; $row++)
                        <li class="sm-row"><span class="sm-thumb"></span><div class="sm-body"><span class="sm-line is-title"></span><span class="sm-line"></span><span class="sm-line is-short"></span></div></li>
                    @endfor
                </ul>
            @elseif (! $this->ready())
                <div class="sm-stack">
                    <p class="sm-note">{{ __('simplify-toolkit::packages.unknown_software') }}</p>
                    {{ $this->chooseAction }}
                </div>
            @else
                @unless ($context['detected'])
                    <x-slot name="afterHeader">
                        {{ ($this->chooseAction)->color('gray')->size('sm') }}
                    </x-slot>
                @endunless
                <div class="sm-search">
                    <x-filament::input.wrapper prefix-icon="tabler-search">
                        <x-filament::input type="search" wire:model.live.debounce.400ms="query" :placeholder="__('simplify-toolkit::packages.search_placeholder')" />
                    </x-filament::input.wrapper>
                    @if ($offline)
                        <p class="sm-note is-warn">{{ __('simplify-toolkit::packages.offline', ['sites' => implode(', ', $offline)]) }}</p>
                    @endif
                </div>

                @if (trim($query) === '' && $results)
                    <p class="sm-note">{{ __('simplify-toolkit::packages.popular_'.($context['kind'] === 'plugins' ? 'plugins' : 'mods'), ['target' => ucfirst($context['loader']).' '.$context['minecraft']]) }}</p>
                @endif
                <ul class="sm-list" wire:loading.class="sm-busy" wire:target="query">
                    @forelse (array_slice($results, 0, $shown) as $card)
                        <li class="sm-row" wire:key="card-{{ $card['key'] }}">
                            @if ($card['icon'])
                                <img src="{{ $card['icon'] }}" alt="" loading="lazy" class="sm-thumb">
                            @else
                                <span class="sm-thumb is-empty"><x-filament::icon icon="tabler-puzzle" /></span>
                            @endif
                            <div class="sm-body">
                                <div class="sm-head">
                                    <a href="{{ $card['page'] }}" target="_blank" rel="noopener" class="sm-title">{{ $card['title'] }}</a>
                                    <x-filament::badge size="sm" color="gray">{{ $card['label'] }}</x-filament::badge>
                                    @unless ($card['fits'])
                                        <x-filament::badge size="sm" color="warning">{{ __('simplify-toolkit::packages.other_version') }}</x-filament::badge>
                                    @endunless
                                </div>
                                <p class="sm-text">{{ $card['description'] }}</p>
                                <p class="sm-meta">
                                    @if ($card['author']){{ $card['author'] }} · @endif{{ __('simplify-toolkit::packages.downloads', ['count' => \Illuminate\Support\Number::abbreviate($card['downloads'])]) }}
                                    @foreach ($card['also'] as $also)
                                        · <a href="{{ $also['page'] }}" target="_blank" rel="noopener">{{ __('simplify-toolkit::packages.also_on', ['site' => $also['label']]) }}</a>
                                    @endforeach
                                </p>
                            </div>
                            <div class="sm-actions">
                                @if ($this->isInstalled($card))
                                    <x-filament::badge color="success" icon="tabler-check">{{ __('simplify-toolkit::packages.is_installed') }}</x-filament::badge>
                                @else
                                    {{ ($this->installAction)(['key' => $card['key'], 'fits' => $card['fits']]) }}
                                @endif
                            </div>
                        </li>
                    @empty
                        @if ($searched)
                            <li class="sm-empty">{{ __('simplify-toolkit::packages.nothing_found') }}</li>
                        @endif
                    @endforelse
                </ul>
                @if (count($results) > $shown)
                    <div class="sm-more">
                        <x-filament::button color="gray" size="sm" wire:click="showMore">{{ __('simplify-toolkit::packages.show_more') }}</x-filament::button>
                    </div>
                @endif
            @endif
        </x-filament::section>

        <div class="sm-side">
            <div @if ($watching) wire:poll.15s="checkStart" @endif>
                <x-filament::section :heading="__('simplify-toolkit::packages.'.($mods ? 'installed_mods' : 'installed_plugins'))" :description="__('simplify-toolkit::packages.restart_help')">
                    @if (! $loaded)
                        <ul class="sm-list sm-skeleton" aria-busy="true">
                            @for ($row = 0; $row < 4; $row++)
                                <li class="sm-row is-compact"><div class="sm-body"><span class="sm-line is-title"></span><span class="sm-line is-short"></span></div></li>
                            @endfor
                        </ul>
                    @else
                    @php
                        // A modpack's own mods fold into one row; mods added here and mods switched off stay visible.
                        $folded = $modpack !== null && ! $showPack;
                        $own = array_values(array_filter($installed, fn (array $item) => $item['source'] !== ''));
                        $packCount = count($installed) - count($own);
                        $rows = $folded ? array_values(array_filter($installed, fn (array $item) => $item['source'] !== '' || $item['off_reason'] !== '')) : $installed;
                        // Plain buttons with the same look as Filament's icon buttons: a Filament action per row made a
                        // modpack's list slow to render and every tooltip slow to start. Clicking still mounts the action.
                        $icons = [];
                        foreach (['tabler-refresh', 'tabler-player-pause', 'tabler-player-play', 'tabler-trash', 'tabler-chevron-down', 'tabler-chevron-up', 'tabler-packages'] as $name) {
                            $icons[$name] = svg($name, 'fi-icon')->toHtml();
                        }
                    @endphp
                    <ul class="sm-list">
                        @if ($modpack !== null)
                            <li class="sm-row is-compact is-pack" wire:key="modpack">
                                @if ($modpack['icon'])
                                    <img src="{{ $modpack['icon'] }}" alt="" loading="lazy" class="sm-thumb">
                                @else
                                    <span class="sm-thumb is-empty">{!! $icons['tabler-packages'] !!}</span>
                                @endif
                                <div class="sm-body">
                                    @php $packName = $modpack['name'] ?: __('simplify-toolkit::packages.modpack'); @endphp
                                    @if ($modpack['url'])
                                        <a href="{{ $modpack['url'] }}" target="_blank" rel="noopener" class="sm-title" style="display: block">{{ $packName }}</a>
                                    @else
                                        <p class="sm-title">{{ $packName }}</p>
                                    @endif
                                    <p class="sm-meta is-one-line">
                                        {{ __('simplify-toolkit::packages.installed_modpack') }}
                                        @if ($modpack['version']) · {{ $modpack['version'] }} @endif
                                        · {{ trans_choice('simplify-toolkit::packages.pack_mods', $packCount, ['count' => $packCount]) }}
                                    </p>
                                </div>
                                @if ($packCount > 0)
                                    @php $packLabel = __('simplify-toolkit::packages.'.($showPack ? 'hide_pack_mods' : 'show_pack_mods')); @endphp
                                    <div class="sm-actions">
                                        <button type="button" class="sm-icon-btn" title="{{ $packLabel }}" aria-label="{{ $packLabel }}" aria-expanded="{{ $showPack ? 'true' : 'false' }}" wire:click="$toggle('showPack')">
                                            {!! $icons[$showPack ? 'tabler-chevron-up' : 'tabler-chevron-down'] !!}
                                        </button>
                                    </div>
                                @endif
                            </li>
                        @endif
                        @forelse ($rows as $item)
                            <li @class(['sm-row is-compact', 'is-off' => ! $item['enabled']]) wire:key="file-{{ $item['file'] }}">
                                <div class="sm-body">
                                    @if ($item['page'])
                                        <a href="{{ $item['page'] }}" target="_blank" rel="noopener" class="sm-title" style="display: block">{{ $item['title'] }}</a>
                                    @else
                                        <p class="sm-title">{{ $item['title'] }}</p>
                                    @endif
                                    <p class="sm-meta is-one-line">
                                        {{ $item['version'] ?: $item['file'] }}
                                        · {{ $item['source'] ? ($sites[$item['source']] ?? $item['source']) : __('simplify-toolkit::packages.'.($modpack !== null ? 'from_pack' : 'by_hand')) }}
                                        @unless ($item['enabled'])
                                            · {{ __('simplify-toolkit::packages.off') }}
                                        @endunless
                                        @if ($item['watched'])
                                            · {{ __('simplify-toolkit::packages.watched') }}
                                        @endif
                                    </p>
                                    @if ($item['off_reason'])
                                        <p class="sm-warn">{{ __('simplify-toolkit::packages.off_after_start') }}: {{ $item['off_reason'] }}</p>
                                    @endif
                                </div>
                                <div class="sm-actions">
                                    @if ($item['off_reason'])
                                        {{ ($this->offLogAction)(['file' => $item['file']]) }}
                                    @endif
                                    @if ($canUpdate && $item['key'] !== '')
                                        <button type="button" class="sm-icon-btn" title="{{ __('simplify-toolkit::packages.update') }}" aria-label="{{ __('simplify-toolkit::packages.update') }}"
                                            wire:click="mountAction('update', {{ \Illuminate\Support\Js::from(['file' => $item['file'], 'title' => $item['title']]) }})">
                                            {!! $icons['tabler-refresh'] !!}
                                        </button>
                                    @endif
                                    @if ($canToggle)
                                        @php $toggleLabel = __('simplify-toolkit::packages.'.($item['enabled'] ? 'switch_off' : 'switch_on')); @endphp
                                        <button type="button" class="sm-icon-btn" title="{{ $toggleLabel }}" aria-label="{{ $toggleLabel }}"
                                            wire:click="mountAction('toggle', {{ \Illuminate\Support\Js::from(['file' => $item['file'], 'enabled' => $item['enabled']]) }})">
                                            {!! $icons[$item['enabled'] ? 'tabler-player-pause' : 'tabler-player-play'] !!}
                                        </button>
                                    @endif
                                    @if ($canRemove)
                                        <button type="button" class="sm-icon-btn is-danger" title="{{ __('simplify-toolkit::packages.remove') }}" aria-label="{{ __('simplify-toolkit::packages.remove') }}"
                                            wire:click="mountAction('remove', {{ \Illuminate\Support\Js::from(['file' => $item['file']]) }})">
                                            {!! $icons['tabler-trash'] !!}
                                        </button>
                                    @endif
                                </div>
                            </li>
                        @empty
                            @if ($modpack === null)
                                <li class="sm-empty">{{ __('simplify-toolkit::packages.'.($mods ? 'no_mods' : 'no_plugins')) }}</li>
                            @endif
                        @endforelse
                    </ul>
                    @endif
                </x-filament::section>
            </div>

            <x-filament::section :heading="__('simplify-toolkit::settings.own_key')" :description="__('simplify-toolkit::settings.'.(\Simplify\Toolkit\Services\Keys::has(\Simplify\Toolkit\Services\Keys::CURSEFORGE, user()) ? 'own_key_saved' : 'own_key_help'))">
                {{-- The button sits in the card's head, like the buttons of Simplify's own cards. --}}
                <x-slot name="afterHeader">
                    {{ ($this->ownKeyAction)->size('sm') }}
                </x-slot>
            </x-filament::section>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
