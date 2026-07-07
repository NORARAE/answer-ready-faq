# Answer-Ready FAQ Block

A WordPress block that treats an FAQ as what it really is: **content for two audiences at once** — the human reading the page, and the search engines and AI answer engines deciding whether to surface it.

Editors manage question/answer pairs in a clean repeater UI. The block renders an accessible, dependency-free accordion on the front end **and** automatically emits a [schema.org `FAQPage`](https://schema.org/FAQPage) JSON-LD graph generated from the same content — so the structured data can never drift out of sync with what visitors see.

Built with the standard WordPress toolchain (`@wordpress/scripts`), block API v3, and a dynamic render callback in modern, namespaced, strictly-typed PHP.

## Why this block exists

Most FAQ schema implementations fail in one of two ways:

1. **Decoupled schema** — JSON-LD is hand-maintained in an SEO plugin field or a theme snippet, separate from the visible content. The moment an editor rewrites an answer, the page is publishing stale (or worse, mismatched) structured data, which violates Google's guideline that schema must reflect visible page content.
2. **Inaccessible accordions** — div/span click handlers with no keyboard support, no disclosure semantics, and broken screen-reader output.

This block solves both with one architectural decision: **the block attributes are the single source of truth**, and the server renders both the visible accordion and the JSON-LD from that one source on every request.

## Architecture decisions (and why)

**Dynamic block, not static save.** The block declares `"render": "file:./render.php"` in `block.json` and its `save()` returns `null`. Rendering server-side means: (a) JSON-LD is always generated from current content, (b) markup improvements ship to all existing posts without block deprecations or content migrations, and (c) escaping happens at output time in PHP, where WordPress's escaping API lives.

**Native `<details>`/`<summary>`, zero front-end JavaScript.** Disclosure widgets are a solved problem at the platform level. Using the native elements gets keyboard operability, screen-reader announcements ("collapsed"/"expanded"), and even in-page find support for free — no ARIA to hand-maintain, no JS bundle to ship, no hydration cost. The disclosure marker is styled, not replaced, and the rotation animation respects `prefers-reduced-motion`.

**Escaping as a contract, enforced twice.** Answers allow a small set of inline formats (`bold`, `italic`, `link`, `code`). The editor constrains this via `RichText`'s `allowedFormats`; the server *enforces* it via `wp_kses()` with an explicit allowlist — because editor-side constraints are UX, not security. Questions and the JSON-LD payload are stripped to plain text per Google's structured-data guidelines, and the graph is encoded with `wp_json_encode()`, never string concatenation.

**Immutable state transformations in the editor.** Every repeater operation (add / update / reorder / remove) is a pure function returning a new array, keeping undo/redo and collaborative editing predictable.

**Theme-agnostic styling.** Front-end CSS asserts structure only — dividers, spacing, the marker — using `currentColor` and `color-mix()` so the block inherits any theme's palette, dark mode included. Colors, spacing, and typography are exposed through `block.json` `supports` so editors adjust them with core controls instead of custom ones.

**An `emitSchema` escape hatch.** If another block or plugin already emits `FAQPage` schema on the page, editors can toggle this block's JSON-LD off from the inspector — duplicate `FAQPage` graphs on one URL is itself a structured-data error.

## Accessibility notes

- Disclosure semantics come from native elements; state is announced by the platform.
- `:focus-visible` outlines are styled, never removed.
- Editor repeater items are labeled groups (`Question 1`, `Question 2` …) so screen-reader users can orient in long lists; reorder/remove controls are real buttons with discernible labels.
- Animation is a single transform transition, disabled under `prefers-reduced-motion`.

## Development

```bash
npm install
npm run start   # develop with live rebuild
npm run build   # production build (also copies render.php via --webpack-copy-php)
npm run lint:js
npm run lint:css
npm run plugin-zip
```

Requires WordPress 6.5+ and PHP 8.0+.

## Structure

```
answer-ready-faq.php        Plugin bootstrap — metadata-driven block registration
src/faq-block/
  block.json                Single source of truth: attributes, supports, assets
  index.js                  Registration (edit only; dynamic block, save() → null)
  edit.js                   Repeater editor UI (immutable state helpers)
  render.php                Server render: accordion markup + FAQPage JSON-LD
  style.scss                Front-end structure-only styles
  editor.scss               Editor-only repeater styles
build/                      Compiled output (generated; not committed)
```

## Roadmap

- Unit tests for the render callback (WP_Mock) and E2E coverage for the repeater (Playwright)
- Optional "single item open at a time" progressive enhancement via a small `viewScript` using the `name` attribute on `<details>`
- `HowTo` sibling block sharing the same architecture

## Author

Built by **Nora G.** ([PlayPlayCode](https://playplaycode.myportfolio.com/))

- Portfolio: [playplaycode.myportfolio.com](https://playplaycode.myportfolio.com/)
- LinkedIn: [linkedin.com/in/ngenetti](https://www.linkedin.com/in/ngenetti/)
- GitHub: [github.com/NORARAE](https://github.com/NORARAE)

## License

GPL-2.0-or-later.
