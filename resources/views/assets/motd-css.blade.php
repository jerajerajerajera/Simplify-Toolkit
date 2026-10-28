/* Minecraft MOTD editor (components/motd-editor.blade.php). A transparent input sits on top of a mirror that
   renders the same text with its colours, so what you type is what players see in their server list. */
.sm-motd { display: flex; flex-direction: column; gap: .75rem; max-width: 36rem; }
.sm-motd-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; }
.sm-motd-colors { display: grid; grid-template-columns: repeat(8, 1.375rem); gap: .3rem; }
.sm-motd-swatch { width: 1.375rem; height: 1.375rem; border-radius: .375rem; border: 1px solid rgb(0 0 0 / .25); cursor: pointer; }
.sm-motd-swatch[aria-pressed="true"] { outline: 2px solid var(--primary-500); outline-offset: 1px; }
.sm-motd-styles { display: flex; flex-wrap: wrap; align-items: center; gap: .125rem; }
.sm-motd-styles [aria-pressed="true"] { background: color-mix(in oklab, var(--primary-500) 18%, transparent); }

/* Minecraft's own colours, so the row looks the same in light and dark themes. */
.sm-motd-row { display: grid; grid-template-columns: 3.5rem minmax(0, 1fr); gap: .25rem .75rem; padding: .625rem .75rem; border-radius: .5rem; background: #202024; }
.sm-motd-icon { grid-row: span 3; width: 3.5rem; height: 3.5rem; border-radius: .25rem; background: #3a3a40; image-rendering: pixelated; object-fit: cover; }
.sm-motd-head { display: flex; justify-content: space-between; gap: .75rem; min-width: 0; color: #fff; font: 15px/24px ui-monospace, monospace; text-shadow: 1px 1px #000; }
.sm-motd-head span:first-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sm-motd-head span:last-child { flex: none; color: #aaa; }
.sm-motd-field { position: relative; min-width: 0; height: 26px; border-radius: .25rem; }
.sm-motd-field:hover { background: rgb(255 255 255 / .04); }
.sm-motd-field:focus-within { background: rgb(255 255 255 / .06); outline: 1px solid rgb(255 255 255 / .35); }
.sm-motd-mirror,
.sm-motd-field input { position: absolute; inset: 0; box-sizing: border-box; width: 100%; height: 100%; margin: 0; padding: 0 4px; border: 0; font: 15px/26px ui-monospace, monospace; letter-spacing: 0; white-space: pre; }
.sm-motd-mirror { overflow: hidden; color: #aaa; text-shadow: 1px 1px #000; pointer-events: none; }
.sm-motd-mirror .is-obfuscated { background: repeating-linear-gradient(135deg, rgb(255 255 255 / .16) 0 2px, transparent 2px 5px); }
.sm-motd-field input { background: transparent; color: transparent; -webkit-text-fill-color: transparent; caret-color: #fff; outline: none; box-shadow: none; }
.sm-motd-field input::selection { background: rgb(120 160 255 / .45); }
.sm-motd-field input::placeholder { color: #6b6b73; -webkit-text-fill-color: #6b6b73; }

.sm-motd-meta { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .375rem .75rem; font-size: .75rem; color: var(--gray-500); }
.sm-motd-meta .is-warn { color: var(--warning-600); }
.sm-motd-link { padding: 0; border: 0; background: none; color: inherit; font: inherit; text-decoration: underline; text-underline-offset: 2px; cursor: pointer; }
.sm-motd-codes { width: 100%; min-height: 4rem; font: .8125rem/1.5 ui-monospace, monospace; }
