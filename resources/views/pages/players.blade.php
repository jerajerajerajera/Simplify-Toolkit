<x-filament-panels::page>
    @once
        <style>@include('simplify-toolkit::assets.pages-css')</style>
    @endonce
    @php
        $head = fn (array|string $player) => 'https://mc-heads.net/avatar/'.rawurlencode(is_array($player) ? ($player['id'] ?: $player['name']) : $player).'/48';
        $sides = array_keys(\Simplify\Toolkit\Services\Players::LISTS);
    @endphp

    @if ($unreachable)
        <p class="sm-note is-danger">{{ __('simplify-toolkit::minecraft.unreachable') }}</p>
    @endif

    <div class="sm-columns" wire:init="load" x-on:simplify-toolkit-players-changed.window="setTimeout(() => $wire.load(), 1500)">
        <x-filament::section :heading="__('simplify-toolkit::players.heading')" :description="__('simplify-toolkit::players.description')">
            <div class="sm-search">
                <x-filament::input.wrapper prefix-icon="tabler-user">
                    <x-filament::input type="text" wire:model.live.debounce.300ms="name" maxlength="17" autocomplete="off" :placeholder="__('simplify-toolkit::players.name_placeholder')" :aria-label="__('simplify-toolkit::players.name')" />
                </x-filament::input.wrapper>
                <div class="sm-buttons">
                    @foreach (array_keys(\Simplify\Toolkit\Services\Players::COMMANDS) as $do)
                        {{ ($this->playerAction)(['do' => $do]) }}
                    @endforeach
                </div>
                @if ($loaded && ! $running)
                    <p class="sm-note is-warn">{{ __('simplify-toolkit::players.offline') }}</p>
                @endif
            </div>

            <h3 class="sm-title">{{ __('simplify-toolkit::players.online') }}</h3>
            <p class="sm-meta">{{ __('simplify-toolkit::players.online_help') }}</p>
            @if (! $loaded)
                <ul class="sm-list sm-skeleton" aria-busy="true">
                    @for ($row = 0; $row < 3; $row++)
                        <li class="sm-row is-compact"><span class="sm-thumb is-small"></span><div class="sm-body"><span class="sm-line is-title"></span></div></li>
                    @endfor
                </ul>
            @else
                <ul class="sm-list">
                    @forelse ($lists['online'] ?? [] as $player)
                        <li class="sm-row is-compact is-pick" wire:key="online-{{ $player }}" wire:click="$set('name', @js($player))">
                            <img src="{{ $head($player) }}" alt="" loading="lazy" class="sm-thumb is-small is-pixel">
                            <div class="sm-body"><p class="sm-title">{{ $player }}</p></div>
                            <x-filament::badge color="success" size="sm">{{ __('simplify-toolkit::players.online_badge') }}</x-filament::badge>
                        </li>
                    @empty
                        <li class="sm-empty">{{ __('simplify-toolkit::players.no_names') }}</li>
                    @endforelse
                </ul>
            @endif
        </x-filament::section>

        <div class="sm-side is-lists">
            @foreach ($sides as $key)
                <x-filament::section :heading="__('simplify-toolkit::players.'.$key)" compact>
                    @if (! $loaded)
                        <ul class="sm-list sm-skeleton" aria-busy="true">
                            @for ($row = 0; $row < 2; $row++)
                                <li class="sm-row is-compact"><span class="sm-thumb is-small"></span><div class="sm-body"><span class="sm-line is-title"></span></div></li>
                            @endfor
                        </ul>
                    @else
                        <ul class="sm-list">
                            @forelse ($lists[$key] ?? [] as $player)
                                <li class="sm-row is-compact is-pick" wire:key="{{ $key }}-{{ $player['name'] }}" wire:click="$set('name', @js($player['name']))">
                                    <img src="{{ $head($player) }}" alt="" loading="lazy" class="sm-thumb is-small is-pixel">
                                    <div class="sm-body"><p class="sm-title">{{ $player['name'] }}</p></div>
                                </li>
                            @empty
                                <li class="sm-empty">{{ __('simplify-toolkit::players.nobody') }}</li>
                            @endforelse
                        </ul>
                    @endif
                </x-filament::section>
            @endforeach
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
