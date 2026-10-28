@php
    $texts = [
        'colors' => [],
        'default_name' => __('simplify-mods::motd.default_name'),
        'extra_lines' => __('simplify-mods::motd.extra_lines'),
        'too_long' => __('simplify-mods::motd.too_long'),
    ];
    foreach (['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'] as $code) {
        $texts['colors.'.$code] = __('simplify-mods::motd.colors.'.$code);
    }
    unset($texts['colors']);
    $styles = [
        'l' => ['tabler-bold', __('simplify-mods::motd.bold')],
        'o' => ['tabler-italic', __('simplify-mods::motd.italic')],
        'n' => ['tabler-underline', __('simplify-mods::motd.underline')],
        'm' => ['tabler-strikethrough', __('simplify-mods::motd.strikethrough')],
        'k' => ['tabler-blur', __('simplify-mods::motd.obfuscated')],
    ];
@endphp
@once
    <style>@include('simplify-mods::assets.motd-css')</style>
    <script>@include('simplify-mods::assets.motd-js')</script>
@endonce
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    {{-- The editor keeps its own state; Livewire only receives the finished MOTD text. --}}
    <div wire:ignore class="sm-motd" x-data="simplifyMotdEditor({ value: $wire.$entangle(@js($getStatePath())), texts: @js($texts), name: () => @js($getServerName()), icon: () => @js($getIconUrl()) })">
        <div class="sm-motd-toolbar" role="toolbar" aria-label="{{ __('simplify-mods::motd.toolbar') }}">
            <div class="sm-motd-colors">
                <template x-for="color in colors" :key="color[0]">
                    <button type="button" class="sm-motd-swatch" :style="{ backgroundColor: color[2] }" :title="color[1]" :aria-label="color[1]" :aria-pressed="colorActive(color[0])" x-on:mousedown.prevent x-on:click="setColor(color[0])"></button>
                </template>
            </div>
            <div class="sm-motd-styles">
                @foreach ($styles as $code => [$icon, $label])
                    <x-filament::icon-button :icon="$icon" color="gray" size="sm" :label="$label" :tooltip="$label" x-bind:aria-pressed="formatActive('{{ $code }}')" x-on:mousedown.prevent x-on:click="toggleFormat('{{ $code }}')" />
                @endforeach
                <x-filament::icon-button icon="tabler-clear-formatting" color="gray" size="sm" :label="__('simplify-mods::motd.clear')" :tooltip="__('simplify-mods::motd.clear')" x-on:mousedown.prevent x-on:click="clearFormatting()" />
            </div>
        </div>
        <div class="sm-motd-row">
            <img class="sm-motd-icon" alt="" x-show="icon()" :src="icon()">
            <span class="sm-motd-icon" x-show="!icon()" aria-hidden="true"></span>
            <div class="sm-motd-head"><span x-text="name()"></span><span>0/20</span></div>
            @foreach ([0, 1] as $line)
                <div class="sm-motd-field">
                    <div class="sm-motd-mirror" x-ref="mirror{{ $line }}" aria-hidden="true"><template x-for="(part, n) in editorRuns({{ $line }})" :key="n"><span :class="{ 'is-obfuscated': part.obfuscated }" :style="part.style" x-text="part.text"></span></template></div>
                    <input type="text" x-ref="line{{ $line }}" aria-label="{{ __('simplify-mods::motd.line', ['number' => $line + 1]) }}" placeholder="{{ __('simplify-mods::motd.line', ['number' => $line + 1]) }}" spellcheck="false" autocomplete="off"
                        x-on:input="edit({{ $line }}, $event)" x-on:keydown="keydown({{ $line }}, $event)" x-on:scroll="scroll({{ $line }}, $event)" x-on:keyup="scroll({{ $line }}, $event)" x-on:click="scroll({{ $line }}, $event)">
                </div>
            @endforeach
        </div>
        <div class="sm-motd-meta">
            <span :class="{ 'is-warn': notice }" x-text="notice || @js(__('simplify-mods::motd.hint'))"></span>
            <span><span x-text="length()"></span>/512 · <button type="button" class="sm-motd-link" x-on:click="showCodes = !showCodes" x-text="showCodes ? @js(__('simplify-mods::motd.hide_codes')) : @js(__('simplify-mods::motd.show_codes'))"></button></span>
        </div>
        <x-filament::input.wrapper x-show="showCodes" x-cloak>
            <textarea class="fi-input sm-motd-codes" aria-label="{{ __('simplify-mods::motd.codes') }}" spellcheck="false" :value="written" x-on:change="editCodes($event)"></textarea>
        </x-filament::input.wrapper>
    </div>
</x-dynamic-component>
