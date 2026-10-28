<x-filament::section :heading="__('simplify-mods::mods.summary.title')" icon="tabler-cube" wire:init="loadStatus">
    <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('simplify-mods::mods.summary.players') }}</dt>
            <dd class="font-medium text-gray-950 dark:text-white">
                @if ($status === null)
                    <x-filament::loading-indicator class="h-4 w-4" />
                @elseif ($status === [])
                    {{ __('simplify-mods::mods.summary.not_answering') }}
                @else
                    {{ __('simplify-mods::mods.summary.online', ['players' => $status['players'], 'max' => $status['max_players']]) }}
                @endif
            </dd>
        </div>
        @if ($software)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('simplify-mods::mods.summary.software') }}</dt>
                <dd class="font-medium text-gray-950 dark:text-white">{{ $software }}</dd>
            </div>
        @endif
        @if ($modpack)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('simplify-mods::mods.summary.modpack') }}</dt>
                <dd class="font-medium text-gray-950 dark:text-white">
                    @if ($modpack['url'])
                        <a href="{{ $modpack['url'] }}" target="_blank" rel="noopener" class="hover:underline">{{ $modpack['name'] }}</a>
                    @else
                        {{ $modpack['name'] }}
                    @endif
                    @if ($modpack['version'])
                        <span class="text-gray-500">{{ $modpack['version'] }}</span>
                    @endif
                </dd>
            </div>
        @endif
        @if ($packagesUrl)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('simplify-mods::packages.'.($mods ? 'mods' : 'plugins')) }}</dt>
                <dd class="font-medium text-gray-950 dark:text-white">{{ __('simplify-mods::mods.summary.installed', ['count' => $count]) }}</dd>
            </div>
        @endif
    </dl>

    @if ($switchedOff)
        <p class="mt-3 text-sm text-warning-600 dark:text-warning-400">{{ __('simplify-mods::mods.summary.switched_off', ['names' => implode(', ', $switchedOff)]) }}</p>
    @endif

    @if ($minecraftUrl || $packagesUrl)
        <div class="mt-4 flex flex-wrap gap-2">
            @if ($minecraftUrl)
                <x-filament::button tag="a" :href="$minecraftUrl" color="gray" size="sm" icon="tabler-adjustments">{{ __('simplify-mods::minecraft.title') }}</x-filament::button>
            @endif
            @if ($packagesUrl)
                <x-filament::button tag="a" :href="$packagesUrl" color="gray" size="sm" icon="tabler-puzzle">{{ __('simplify-mods::packages.'.($mods ? 'mods' : 'plugins')) }}</x-filament::button>
            @endif
        </div>
    @endif
</x-filament::section>
