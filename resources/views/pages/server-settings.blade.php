<x-filament-panels::page>
    @once
        <style>@include('simplify-toolkit::assets.pages-css')</style>
    @endonce
    {{-- data-simplify-unsaved: Simplify shows "Changes made" with Discard and Save, and asks before leaving (ADDONS.md, "Unsaved changes"). --}}
    {{-- The list of sections follows the scrolling and jumps to a section; an address with #settings-… opens there. --}}
    <form wire:submit="save" data-simplify-unsaved="data" class="sm-settings" x-data="{
        open: 'settings-server',
        init() {
            const watch = new IntersectionObserver((seen) => {
                const first = seen.filter((entry) => entry.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
                if (first) this.open = first.target.id;
            }, { rootMargin: '0px 0px -60% 0px' });
            this.$root.querySelectorAll('.sm-settings-body [id^=settings-]').forEach((section) => watch.observe(section));
            if (location.hash.startsWith('#settings-')) this.$nextTick(() => this.go(location.hash.slice(1)));
        },
        go(id) {
            this.open = id;
            document.getElementById(id)?.scrollIntoView({ block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        },
    }">
        {{ $this->form }}
    </form>
</x-filament-panels::page>
