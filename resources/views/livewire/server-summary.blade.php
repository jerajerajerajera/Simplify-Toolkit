<div wire:init="loadStatus">
    @once
        <style>@include('simplify-mods::assets.pages-css')</style>
    @endonce
    <x-filament::section :heading="__('simplify-mods::mods.summary.title')" icon="tabler-cube">
        <dl class="sm-facts">
            <div>
                <dt>{{ __('simplify-mods::mods.summary.players') }}</dt>
                <dd>
                    @if ($status === null)
                        <x-filament::loading-indicator style="width: 1rem; height: 1rem" />
                    @elseif ($status === [])
                        {{ __('simplify-mods::mods.summary.not_answering') }}
                    @else
                        {{ __('simplify-mods::mods.summary.online', ['players' => $status['players'], 'max' => $status['max_players']]) }}
                    @endif
                </dd>
            </div>
            @if ($software)
                <div>
                    <dt>{{ __('simplify-mods::mods.summary.software') }}</dt>
                    <dd>{{ $software }}</dd>
                </div>
            @endif
            @if ($modpack)
                <div>
                    <dt>{{ __('simplify-mods::mods.summary.modpack') }}</dt>
                    <dd>
                        @if ($modpack['url'])
                            <a href="{{ $modpack['url'] }}" target="_blank" rel="noopener" style="text-decoration: underline">{{ $modpack['name'] }}</a>
                        @else
                            {{ $modpack['name'] }}
                        @endif
                        @if ($modpack['version'])
                            <span class="is-quiet">{{ $modpack['version'] }}</span>
                        @endif
                    </dd>
                </div>
            @endif
            @if ($packagesUrl)
                <div>
                    <dt>{{ __('simplify-mods::packages.'.($mods ? 'mods' : 'plugins')) }}</dt>
                    <dd>{{ __('simplify-mods::mods.summary.installed', ['count' => $count]) }}</dd>
                </div>
            @endif
        </dl>

        @if ($switchedOff)
            <p class="sm-note is-warn">{{ __('simplify-mods::mods.summary.switched_off', ['names' => implode(', ', $switchedOff)]) }}</p>
        @endif

        @if ($minecraftUrl || $packagesUrl)
            <div class="sm-buttons">
                @if ($minecraftUrl)
                    <x-filament::button tag="a" :href="$minecraftUrl" color="gray" size="sm" icon="tabler-adjustments">{{ __('simplify-mods::minecraft.title') }}</x-filament::button>
                @endif
                @if ($packagesUrl)
                    <x-filament::button tag="a" :href="$packagesUrl" color="gray" size="sm" icon="tabler-puzzle">{{ __('simplify-mods::packages.'.($mods ? 'mods' : 'plugins')) }}</x-filament::button>
                @endif
            </div>
        @endif
    </x-filament::section>
</div>
