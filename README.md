# Answer-Ready FAQ Block

[![CI](https://github.com/NORARAE/answer-ready-faq/actions/workflows/ci.yml/badge.svg)](https://github.com/NORARAE/answer-ready-faq/actions/workflows/ci.yml)

**An accessible WordPress FAQ block that writes its own SEO structured data — content and schema can never drift apart.**

| | |
|---|---|
| ⚛️ **React editor UI** | Add, reorder, and remove Q&A pairs with undo/redo |
| ♿ **Accessible by default** | Native disclosure elements, full keyboard support, zero front-end JS |
| 🤖 **AI-search ready** | Auto-generated schema.org FAQPage JSON-LD on every render |
| 🔒 **Escaped twice** | Editor constraints + server-side `wp_kses()` allowlist |
| 🎨 **Theme-agnostic** | Inherits any theme's colors and type, dark mode included |
| 🔌 **Headless-ready** | Read-only REST endpoint serving the same answers *and* the same JSON-LD |

A WordPress block that treats an FAQ as what it really is: **content for two audiences at once** — the human reading the page, and the search engines and AI answer engines deciding whether to surface it.

Editors manage question/answer pairs in a clean repeater UI. The block renders an accessible, dependency-free accordion on the front end **and** automatically emits a [schema.org `FAQPage`](https://schema.org/FAQPage) JSON-LD graph generated from the same content — so the structured data can never drift out of sync with what visitors see.

Built with the standard WordPress toolchain (`@wordpress/scripts`), block API v3, and a dynamic render callback in modern, namespaced, strictly-typed PHP.

## ▶️ Try it live

**[One-click demo in WordPress Playground →](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/NORARAE/answer-ready-faq/main/blueprint.json)**

*A temporary WordPress site spins up in your browser with the plugin installed and a demo FAQ page already published. Nothing to install. ~15s first load.*

The demo page also lets you open the block in the real editor, look at the plugin on the Plugins screen, and expand a panel showing the `FAQPage` JSON-LD **that page is publishing at that moment** — read back out of the rendered HTML, not a copy pasted into a docs example.

There is a second page, **See it headless**, that uses no FAQ block at all: a small React app fetches the REST endpoint below and draws the accordion itself, with a panel showing the raw JSON it received.

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

## The REST endpoint

```
GET /wp-json/answer-ready/v1/faqs/<post_id>
```

Public, read-only, and cacheable. It returns a post's FAQ pairs together with the `FAQPage` graph that post publishes:

```json
{
  "postId": 4,
  "title": "See it working",
  "url": "https://example.com/faq-demo/",
  "modified": "2026-09-26T17:29:00+00:00",
  "count": 5,
  "faqs": [ { "question": "…", "answer": "…" } ],
  "schema": { "@context": "https://schema.org", "@type": "FAQPage", "mainEntity": [ … ] }
}
```

**The endpoint is not a second implementation.** It and the block's render callback both call `AnswerReadyFAQ\Data\build_schema()` on the output of the same normaliser, so the JSON a headless client fetches and the JSON-LD a crawler reads on the page are the same graph by construction — not by discipline. `tests/SingleSourceTest.php` fails if anyone gives either path a graph of its own.

Notes on the contract:

- **Public means published.** Drafts, pending and private posts, revisions, password-protected posts and non-public post types all return the same 404, so the endpoint cannot be used to probe for unpublished content.
- **`answer` arrives pre-sanitised** through the same inline-only `wp_kses()` allowlist the page renders with, so a client can print it without re-sanitising.
- **`schema` answers are plain text**, per Google's structured-data guidance.
- **Cached** in a transient keyed by the post's modified time, so an edit invalidates it rather than waiting out a TTL, and sent with `Cache-Control: public, max-age=300, s-maxage=3600`.

See [`examples/headless-nextjs/`](examples/headless-nextjs/) for a Next.js App Router page that consumes it.

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

Tests run on plain PHP — no WordPress install, no database, no `wp-env`:

```bash
composer install
composer test
```

The data layer leans on only `wp_strip_all_tags()` and `wp_kses()`, both stubbed in `tests/bootstrap.php`, which keeps the suite fast enough to run on every save. It covers the normaliser, the schema shape, the escaping contract, and the single-source guarantee above.

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
includes/
  faq-data.php              Normalise, sanitise, build schema — the one source
  rest.php                  GET /answer-ready/v1/faqs/<post_id>
tests/                      PHPUnit, no WordPress install required
examples/headless-nextjs/   Next.js App Router page consuming the endpoint
blueprint.json              WordPress Playground demo definition
demo/mu-plugins/
  playplayai-demo-skin.php  Demo-only presentation layer (see below)
tools/build-blueprint.mjs   Inlines the demo skin into blueprint.json
```

## The Playground demo

Everything the demo adds lives in one must-use plugin, `demo/mu-plugins/playplayai-demo-skin.php`, which `blueprint.json` writes into `wp-content/mu-plugins` when the site boots. It supplies the dark navy and gold skin, the welcome strip, the schema panel, an admin pointer on the Plugins menu, and the footer.

It is strictly a presentation layer. It adds no filters to the block's attributes, changes none of its markup, and generates no structured data of its own — the schema panel *observes* `render_block`, lifts the JSON-LD the block already emitted, and pretty-prints it. Delete the file and the block behaves exactly as it does on any other site.

Because a `writeFile` step has to carry the PHP inside a JSON string, the file is kept as a real, lintable file and copied into the blueprint by a script:

```bash
npm run build:blueprint   # inline demo/mu-plugins/*.php into blueprint.json
npm run check:blueprint   # fail if blueprint.json is out of date
```

## Roadmap

- E2E coverage for the repeater (Playwright)
- Optional "single item open at a time" progressive enhancement via a small `viewScript` using the `name` attribute on `<details>`
- `HowTo` sibling block sharing the same architecture and the same endpoint

## Author

Built by **PlayPlayAI**.

- Site: [playplayai.com](https://playplayai.com)
- Bloom: [bloom.playplayai.com](https://bloom.playplayai.com)
- LinkedIn: [linkedin.com/in/ngenetti](https://www.linkedin.com/in/ngenetti)
- GitHub: [github.com/NORARAE](https://github.com/NORARAE)

## License

GPL-2.0-or-later.
