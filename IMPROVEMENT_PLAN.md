# WP Social Share — Analysis & Improvement Plan

Analysis date: 2026-07-20 · Plugin version reviewed: 1.0 (`toptal-social-share.php` @ `b308de7`)

---

## 1. Current State

Single-file plugin (~690 lines) that renders Facebook, Twitter, LinkedIn, Pinterest and
WhatsApp share buttons on posts/pages/CPTs, in four locations (below title, after content,
inside featured image, floating left bar), with per-network color customisation, three
appearance styles, three sizes, a `[toptal_ss]` shortcode, and click-based share counts
stored in post meta via an AJAX endpoint.

Assets: Font Awesome 4 (webfonts + CSS), one admin JS (color picker), one frontend JS
(share-count AJAX), one stylesheet.

---

## 2. CRITICAL — the plugin does not run at all (P0)

`php -l toptal-social-share.php` fails on both `master` and this branch:

```
PHP Parse error: syntax error, unexpected identifier "register_setting",
expecting "function" in toptal-social-share.php on line 197
```

The plugin fatals on load and cannot be activated. Root causes (all look like leftovers
from the Google+ button removal):

| Location | Problem |
|---|---|
| `toptal-social-share.php:187` | Stray `}` closes the `if (get_option('toptal_ss_color') == 0)` block too early |
| `toptal-social-share.php:194` | The following `}` then closes `toptal_ss_settings()` itself, dumping the `register_setting()` calls (lines 197–221) into class-body scope → parse error. It also leaves the WhatsApp color fields (188–193) outside the "custom colors" condition |
| `toptal-social-share.php:245-246` | Orphaned `<?php }` fragment — body of a deleted checkbox callback (Google+) |
| `toptal-social-share.php:332-333` | Same orphaned fragment among the color-picker callbacks |
| `toptal-social-share.php:360-361` | Same orphaned fragment among the background-color callbacks |

**Action:** remove the three orphaned fragments, re-balance the braces in
`toptal_ss_settings()`, then gate merges on `php -l` (see Phase 4 CI). Nothing else in
this plan matters until this is fixed.

---

## 3. Security Audit Findings

### High

1. **AJAX share-count endpoint has no nonce, no origin check, no rate limiting**
   (`toptal_update_share_count()`, line 641; exposed via `wp_ajax_nopriv_`).
   Anyone can POST to `admin-ajax.php` and:
   - inflate share counts arbitrarily (bot-scriptable, no dedupe, no throttle);
   - write `toptal_ss_share_counts` post meta onto **any** post ID — including drafts,
     private posts, revisions, nav menu items — because the ID is only `intval()`'d,
     never validated against an existing, published, public post.

   **Fix:** create a nonce with `wp_create_nonce()`, pass it via the existing
   `wp_localize_script()` payload, verify with `check_ajax_referer()`; validate
   `get_post_status($post_id) === 'publish'` and that the post type is viewable
   (`is_post_type_viewable()`); add a lightweight transient/IP-based throttle so one
   client can't increment in a loop. Longer term, move to the REST API
   (`register_rest_route`) instead of `admin-ajax.php`.

2. **Share URLs are built by string concatenation without URL-encoding the permalink**
   (`toptal_social_html()`, lines 495–512). `esc_url(get_permalink())` is applied, but the
   result is then embedded as a query-string value in the network URLs without
   `rawurlencode()`. Permalinks containing `&`, `?` or `#` truncate/alter the share target,
   and it's the wrong layering (escape-then-concat instead of encode-then-escape).

   **Fix:** build each share URL with `add_query_arg()` / `rawurlencode()` on the raw
   permalink, and `esc_url()` only at output time.

### Medium

3. **Unsanitized-by-modern-standards settings registration.** `register_setting()` is
   called with a bare `'intval'` / `'strval'` string callback. `strval` performs **no
   sanitisation** on `toptal_ss_size` (any string is stored verbatim). It's currently not
   exploitable only because output goes through a `switch` whitelist — a fragile,
   incidental protection. **Fix:** register every setting with the args-array form and a
   real sanitize callback (checkbox → `rest_sanitize_boolean`/custom 0-1, size → whitelist
   against `['small','medium','large']`, colors → `sanitize_hex_color` as already done).

4. **Unescaped echo of assembled HTML** in `toptal_float_area()` (line 638) and the
   filters. The HTML is internally generated and attribute values go through
   `esc_attr`/`esc_url`, so there is no direct injection today, but the pattern (giant
   string concat, `echo $html`) makes future regressions easy and un-reviewable.
   **Fix:** move markup into a small template/render method using `printf` with per-value
   escaping, and add `wp_kses` on the final output of the shortcode/filters as a backstop.

5. **Shortcode attributes are used unsanitized** (`toptal_ss_cb()`): `$atts['size']` falls
   through a `switch` (safe today), but `$a['facebook'] == 1` etc. rely on loose compare.
   Sanitize/cast all attributes at the top of the callback.

6. **`$post` null dereferences.** `toptal_add_social_share_icons()`, `toptal_ss_cb()` and
   `toptal_float_area()` use `global $post` / `get_the_ID()` without checking. On feeds,
   search, REST, or any context where `the_content`/`the_title` runs without a post object,
   `$post->ID` fatals (and with `declare(strict_types=1)`, `toptal_social_html(false)`
   throws a `TypeError` from `get_the_ID()` returning `false`). Guard with
   `is_singular()` / `in_the_loop()` / `$post instanceof WP_Post`.

### Low / hygiene

7. **`the_title` filter injects HTML into titles** (line 45). `in_the_loop()` narrows it,
   but `the_title` also fires for widgets and secondary loops, and any theme that escapes
   the title (correctly) will print the button markup as text. Prefer hooking a dedicated
   action or prepending inside `the_content` instead.
8. **Ancient user-agent sniffing** (`toptal_ss_is_mobile()`, lines 580–602): substrings
   like `"lg"`, `"pt"`, `"pg"`, `"xx"`, `"audio"` false-positive on many desktop UAs, and
   any server-side detection breaks under page caching. Replace with `wp_is_mobile()` at
   minimum; better, render the WhatsApp button always and show/hide via CSS media query
   or `navigator.share` feature detection.
9. **Uninstall cleanup is incomplete**: options are deleted but `toptal_ss_share_counts`
   post meta is left behind. Also `register_uninstall_hook()` runs in the constructor on
   every request (needless option write). Move cleanup to an `uninstall.php` file and
   include `delete_post_meta_by_key('toptal_ss_share_counts')`.
10. **Assets enqueued site-wide with no version** (`wp_register_style/script(..., false)`):
    Font Awesome + CSS + JS load on every page even when no buttons render, and version
    `false` breaks cache-busting on updates. Enqueue conditionally and pass the plugin
    version constant.
11. **Race condition** in the count increment (read-modify-write of post meta) loses
    counts under concurrency — acceptable for vanity metrics, but worth a note or an
    atomic `$wpdb` update if counts ever matter.
12. **"TopTal" branding**: the plugin carries a third-party company's trademark in its
    name, slug, and text domain. Rename (e.g. to "WP Social Share", slug
    `wp-social-share`) before any public/wordpress.org distribution.

---

## 4. Code-Quality Issues

- **Broken/deprecated share endpoints**: `https://twitter.com/home?status=` no longer
  works — use `https://twitter.com/intent/tweet?url=` (or an X-branded intent). In the
  "Just icon" style (case 1), the WhatsApp link is **missing entirely**, so the icon-only
  mode silently drops WhatsApp; Pinterest works better with `media` + `description` args.
- **Dead code**: the entire commented-out "Order" section (lines 129–150) should be
  deleted (and reimplemented properly — see features).
- **Duplication**: five near-identical checkbox callbacks, ten near-identical color-picker
  callbacks, and two near-identical branches in `toptal_social_html()` (settings vs
  shortcode). A single `$networks` config array would collapse ~300 lines into ~60 and
  make adding a network a one-line change.
- **17 scalar options** instead of one settings array — every render triggers a dozen
  `get_option()` calls, and `toptal_ss_settings()` calls `get_option()` inside `admin_init`
  to conditionally register fields (so the color fields only appear after a second save).
- **Single-file architecture** with mixed concerns (settings UI, rendering, AJAX,
  detection). Split into classes: `Settings`, `Renderer`, `Ajax`, `Assets`, `Plugin`.
- **Inconsistent style**: tabs/spaces mixed, some methods typed (`: void`), most not,
  missing visibility keywords, misspelled docblocks ("bottons", "iamge").
- **i18n incomplete**: no `load_plugin_textdomain()` call, no `.pot` file, no
  `Text Domain:`/`Domain Path:` headers, so none of the `__()` strings are translatable.
- **Font Awesome 4** (EOL) adds ~450 KB of webfonts for five icons — replace with inline
  SVGs.
- **No tests, no CI, no coding-standard tooling** — which is exactly how the P0 parse
  error shipped to master through two merged PRs.

---

## 5. Feature Roadmap

### Quick wins
- **More networks**: X, Reddit, Telegram, Email (mailto), Mastodon, Bluesky, Threads —
  trivial once networks are config-driven.
- **Copy-link button** with clipboard API + "copied" feedback.
- **Native share**: use the Web Share API (`navigator.share`) on supporting devices —
  replaces the UA-sniffing WhatsApp hack entirely.
- **Finish the ordering feature** (currently commented out) as a drag-and-drop sortable
  list on the settings page, stored as one array option.

### Medium
- **Gutenberg block** (`block.json`, server-rendered) so buttons can be placed in the
  editor; keep the shortcode for classic themes. Add a widget and a
  `do_action('wp_social_share')` template tag.
- **Per-post controls**: meta box / block-editor panel to hide buttons or override
  networks on individual posts.
- **Share-count display options**: toggle counts on/off, "hide until N", total-only mode;
  optionally fetch real counts from network APIs with transient caching.
- **Floating bar options**: left/right position, top offset, mobile bottom-sticky bar,
  hide-on-scroll.
- **UTM parameters**: optional `utm_source=<network>&utm_medium=social` appended to
  shared URLs for analytics attribution.
- **Analytics mini-dashboard**: "most shared posts" admin page / dashboard widget backed
  by the existing post-meta counts.

### Larger
- **Settings screen rebuild** (React/`@wordpress/components` or clean Settings API with
  tabs), with live preview, import/export of settings, and a proper onboarding default
  state (sane defaults on activation — currently a fresh install shows nothing until
  every checkbox is ticked).
- **Open Graph / Twitter Card meta output** (optional, off by default, with conflict
  detection against SEO plugins).
- **Multisite support** (network-activate correctly, per-site settings).
- **Accessibility pass**: `aria-label` on every link, visible focus states, WCAG-AA
  contrast for default colors, `rel="nofollow"` option.

---

## 6. Phased Plan

**Phase 0 — Make it work (blocking, ~1 day)**
1. Fix the parse errors (§2) and restore correct brace structure.
2. Fix the broken Twitter endpoint and the missing WhatsApp link in icon-only mode.
3. Add null-`$post` guards (finding 6).
4. Manual smoke test: activate, save settings, render all four locations + shortcode.

**Phase 1 — Security hardening (~2–3 days)**
1. Nonce + post-status validation + throttling on the AJAX endpoint (finding 1);
   move it to a REST route.
2. Proper URL building with `rawurlencode`/`add_query_arg` (finding 2).
3. Args-array `register_setting()` with real sanitize callbacks for every option
   (finding 3); sanitize shortcode atts (finding 5).
4. `uninstall.php` with full cleanup including post meta (finding 9).
5. Run PHPCS `WordPress-Extra` + the `WordPress.Security` sniffs; fix everything it flags.

**Phase 2 — Refactor (~3–5 days)**
1. Config-driven `$networks` array; collapse duplicated callbacks and render branches.
2. Split into namespaced classes with an autoloader; keep the main file as bootstrap.
3. Migrate the 17 options to a single array option (with a one-time migration routine).
4. Conditional, versioned asset loading; replace Font Awesome with inline SVGs; drop the
   jQuery dependency in frontend JS (vanilla `fetch`).
5. Replace UA sniffing with Web Share API / CSS.
6. i18n: text domain loading, headers, generate `.pot`.

**Phase 3 — Features (iterative)**
Quick wins first (new networks, copy link, ordering, count display options), then the
Gutenberg block and per-post controls, then floating-bar/UTM/analytics, then the larger
items — each as its own PR.

**Phase 4 — Tooling & distribution (parallel, ~1–2 days)**
1. GitHub Actions CI: `php -l` matrix (7.4–8.3), PHPCS, PHPUnit + Brain Monkey unit
   tests for `pre_validate`, sanitizers, URL building, and the AJAX handler.
2. `composer.json` (dev deps), `.editorconfig`, contributing notes.
3. Rename away from the TopTal trademark; add `readme.txt` (wp.org format), changelog,
   semantic versioning, and a release workflow that builds a distributable zip.

---

## 7. Priority Summary

| Priority | Item |
|---|---|
| 🔴 P0 | Fix fatal parse error — plugin is currently dead code |
| 🔴 P1 | AJAX endpoint: nonce, post validation, throttling |
| 🔴 P1 | Broken Twitter share URL; missing WhatsApp link in icon mode |
| 🟠 P2 | Proper sanitize callbacks; URL encoding; `$post` guards; uninstall cleanup |
| 🟠 P2 | CI (lint/PHPCS/tests) so a parse error can never merge again |
| 🟡 P3 | Refactor to config-driven networks + class structure + single option |
| 🟡 P3 | Quick-win features: new networks, copy link, Web Share, ordering |
| 🟢 P4 | Gutenberg block, per-post controls, analytics, rename & wp.org packaging |
