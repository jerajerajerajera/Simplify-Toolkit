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

    <div class="sm-columns">
        <x-filament::section :heading="__('simplify-toolkit::packages.find')" :description="$this->ready() ? __('simplify-toolkit::packages.for', ['loader' => ucfirst($context['loader']), 'version' => $context['minecraft']]) : null">
            @if (! $this->ready())
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
                    <ul class="sm-list">
                        @forelse ($installed as $item)
                            <li @class(['sm-row is-compact', 'is-off' => ! $item['enabled']]) wire:key="file-{{ $item['file'] }}">
                                <div class="sm-body">
                                    @if ($item['page'])
                                        <a href="{{ $item['page'] }}" target="_blank" rel="noopener" class="sm-title" style="display: block">{{ $item['title'] }}</a>
                                    @else
                                        <p class="sm-title">{{ $item['title'] }}</p>
                                    @endif
                                    <p class="sm-meta is-one-line">
                                        {{ $item['version'] ?: $item['file'] }}
                                        · {{ $item['source'] ? ($sites[$item['source']] ?? $item['source']) : __('simplify-toolkit::packages.by_hand') }}
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
                                    {{ ($this->toggleAction)(['file' => $item['file'], 'enabled' => $item['enabled']]) }}
                                    {{ ($this->removeAction)(['file' => $item['file']]) }}
                                </div>
                            </li>
                        @empty
                            <li class="sm-empty">{{ __('simplify-toolkit::packages.'.($mods ? 'no_mods' : 'no_plugins')) }}</li>
                        @endforelse
                    </ul>
                </x-filament::section>
            </div>

            <x-filament::section :heading="__('simplify-toolkit::settings.own_key')" :description="__('simplify-toolkit::settings.'.(\Simplify\Toolkit\Services\Keys::has(\Simplify\Toolkit\Services\Keys::CURSEFORGE, user()) ? 'own_key_saved' : 'own_key_help'))">
                {{ ($this->ownKeyAction)->size('sm') }}
            </x-filament::section>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
