{{-- Minecraft, on a phone: a row of common commands just above Pelican's command line. A tap puts one in the line to
     finish or send. The row lives in the terminal's own box (wire:ignore), so redraws of the console keep it. It lies
     over the bottom of the output, which gets that much more room below (xterm's own padding, which Pelican's fit counts),
     so it never covers the console's title row or a line of output. --}}
<style>
    .sm-quick-commands { display: none; }
    @media (max-width: 600px) {
        #terminal:has(> .sm-quick-commands) { position: relative; }
        #terminal:has(> .sm-quick-commands) > .xterm { padding-bottom: 62px; }
        #terminal > .sm-quick-commands { position: absolute; inset: auto 0 0; z-index: 11; display: flex; gap: 6px; padding: 8px 12px; overflow-x: auto; scrollbar-width: none; border-top: 1px solid var(--ps-console-line, rgb(255 255 255 / .1)); background: #131a20; /* the console card's own colour (Simplify's dashboard.css) */ }
        .sm-quick-commands::-webkit-scrollbar { display: none; }
        .sm-quick-command { flex: none; min-height: 36px; padding: 0 12px; border: 1px solid var(--ps-console-line, rgb(255 255 255 / .12)); border-radius: 999px; background: var(--ps-console-raised, rgb(255 255 255 / .06)); color: var(--ps-console-ink, #e5e7eb); font: 12px var(--ps-mono, ui-monospace, Menlo, Consolas, monospace); white-space: nowrap; }
        .sm-quick-command:focus-visible { outline: 2px solid var(--ps-accent, var(--primary-500)); outline-offset: 1px; }
    }
</style>
<script>
    (() => {
        const commands = @js(\Simplify\Toolkit\Services\QuickCommands::LIST);
        const label = @js(__('simplify-toolkit::toolkit.quick_commands'));
        const add = () => {
            const terminal = document.getElementById('terminal');
            const input = document.getElementById('send-command');
            if (!terminal || !input) return false;
            if (input.readOnly || terminal.querySelector(':scope > .sm-quick-commands')) return true;
            const row = document.createElement('div');
            row.className = 'sm-quick-commands';
            row.setAttribute('role', 'group');
            row.setAttribute('aria-label', label);
            for (const text of commands) {
                const button = Object.assign(document.createElement('button'), { type: 'button', className: 'sm-quick-command', textContent: text.endsWith(' ') ? text + '…' : text });
                button.addEventListener('click', () => {
                    input.value = text;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.focus();
                    input.setSelectionRange(text.length, text.length);
                });
                row.append(button);
            }
            terminal.append(row);
            // The output got room below for the row: Pelican fits the console again on a resize.
            window.dispatchEvent(new Event('resize'));

            return true;
        };
        // The console draws itself after this script; try again for a few seconds.
        let tries = 0;
        const wait = () => { if (!add() && ++tries < 50) setTimeout(wait, 100); };
        wait();
    })();
</script>
