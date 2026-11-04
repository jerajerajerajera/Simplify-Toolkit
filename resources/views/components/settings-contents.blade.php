{{-- The sections of the Settings page: a list beside them on a wide screen, a row of chips above them on a phone. --}}
<nav class="sm-settings-contents" aria-label="{{ __('simplify-toolkit::toolkit.settings_page.sections') }}">
    @foreach ($contents as $item)
        <a href="#{{ $item['id'] }}" x-on:click.prevent="go(@js($item['id']))" x-bind:class="{ 'is-open': open === @js($item['id']) }">
            <x-filament::icon :icon="$item['icon']" class="sm-settings-icon" />
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
