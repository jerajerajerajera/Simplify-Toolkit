/* The Plugins/Mods page, the log lines and the server summary. Pelican's stylesheet only has the Tailwind classes
   the panel itself uses, so the layout of these views lives here. */
.sm-columns { display: grid; gap: 1.5rem; align-items: start; }
@media (min-width: 1024px) { .sm-columns { grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); } }
.sm-list { list-style: none; margin: 0; padding: 0; }
.sm-list > li + li { border-top: 1px solid var(--gray-200); }
.dark .sm-list > li + li { border-color: rgb(255 255 255 / .1); }
.sm-row { display: flex; align-items: flex-start; gap: .75rem; padding: .75rem 0; }
.sm-row.is-compact { align-items: center; padding: .5rem 0; }
.sm-row.is-off .sm-body { opacity: .55; }
.sm-thumb { width: 2.5rem; height: 2.5rem; flex: none; border-radius: .375rem; object-fit: cover; }
.sm-thumb.is-empty { display: flex; align-items: center; justify-content: center; background: var(--gray-100); color: var(--gray-400); }
.dark .sm-thumb.is-empty { background: rgb(255 255 255 / .05); }
.sm-thumb.is-empty svg { width: 1.25rem; height: 1.25rem; }
.sm-body { min-width: 0; flex: 1 1 auto; }
.sm-head { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem .5rem; }
.sm-title { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; font-size: .875rem; color: var(--gray-950); }
.dark .sm-title { color: #fff; }
a.sm-title:hover, .sm-meta a:hover { text-decoration: underline; }
.sm-text { margin-top: .125rem; font-size: .875rem; color: var(--gray-600); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; }
.dark .sm-text { color: var(--gray-400); }
.sm-meta { margin-top: .25rem; font-size: .75rem; color: var(--gray-500); overflow-wrap: anywhere; }
.sm-meta.is-one-line { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sm-warn { margin-top: .125rem; font-size: .75rem; color: var(--warning-600); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; }
.dark .sm-warn { color: var(--warning-400); }
.sm-note { font-size: .875rem; color: var(--gray-600); }
.dark .sm-note { color: var(--gray-400); }
.sm-note.is-danger { color: var(--danger-600); }
.sm-note.is-warn { margin-top: .75rem; font-size: .75rem; color: var(--warning-600); }
.sm-empty { padding: 1.5rem 0; text-align: center; font-size: .875rem; color: var(--gray-500); }
.sm-actions { display: flex; flex: none; align-items: center; gap: .75rem; }
.sm-stack { display: flex; flex-direction: column; align-items: flex-start; gap: .75rem; }
.sm-more { display: flex; justify-content: center; padding-top: .75rem; }
.sm-search { margin-bottom: 1rem; }
.sm-busy { opacity: .6; }
.sm-log { max-height: 24rem; overflow: auto; margin: 0; padding: .75rem; border-radius: .5rem; background: #0b0b0f; color: #e5e7eb; font-size: .75rem; line-height: 1.25rem; white-space: pre-wrap; overflow-wrap: anywhere; }
.sm-facts { display: grid; gap: .75rem 1.5rem; margin: 0; font-size: .875rem; }
@media (min-width: 640px) { .sm-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.sm-facts dt { color: var(--gray-500); }
.sm-facts dd { margin: 0; font-weight: 500; color: var(--gray-950); }
.dark .sm-facts dd { color: #fff; }
.sm-facts dd .is-quiet { font-weight: 400; color: var(--gray-500); }
.sm-buttons { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
.sm-side { display: flex; flex-direction: column; gap: 1.5rem; }
/* Icon buttons in a row look like Simplify's own icon buttons: a quiet bordered square, the icon turns the accent
   colour on hover. Simplify's colours when it is installed, Pelican's otherwise. */
.sm-actions .fi-icon-btn { display: inline-grid; place-items: center; width: var(--s-h, 36px); height: var(--s-h, 36px); margin: 0; border: 1px solid var(--s-line, var(--gray-200)); border-radius: var(--s-radius-sm, 6px); background: var(--s-surface, #fff); color: var(--s-ink, var(--gray-950)); transition: background .15s, border-color .15s, color .15s; }
.dark .sm-actions .fi-icon-btn { border-color: var(--s-line, var(--gray-700)); background: var(--s-surface, var(--gray-900)); color: var(--s-ink, var(--gray-50)); }
.sm-actions .fi-icon-btn .fi-icon { width: 18px; height: 18px; color: inherit; }
.sm-actions .fi-icon-btn:hover:not(:disabled) { border-color: var(--s-accent, var(--primary-600)); background: var(--s-raised, var(--gray-100)); color: var(--s-accent, var(--primary-600)); }
.dark .sm-actions .fi-icon-btn:hover:not(:disabled) { border-color: var(--s-accent, var(--primary-500)); background: var(--s-raised, var(--gray-800)); color: var(--s-accent, var(--primary-500)); }
.sm-actions .fi-icon-btn.fi-color-danger:not(:hover) { color: var(--s-danger, var(--danger-600)); }
.sm-actions .fi-icon-btn.fi-color-warning:not(:hover) { color: var(--s-warn, var(--warning-700)); }
.sm-actions { gap: .5rem; }
