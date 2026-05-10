# Klingon for WooCommerce

Adds **Klingon (tlhIngan Hol)** as a selectable language in WordPress, complete with WooCommerce translations covering PHP, server-side templates, and JavaScript-rendered UI (Cart/Checkout blocks, the admin React UI, etc.).

- **Contributors:** riaanknoetze
- **Tags:** klingon, language, locale, woocommerce, i18n, tlhIngan
- **Requires at least:** 6.4 (WP 6.5+ recommended for `.l10n.php` fast-load support)
- **Tested up to:** 6.7
- **Requires PHP:** 7.4
- **Stable tag:** 1.0.0
- **License:** GPLv2 or later — https://www.gnu.org/licenses/gpl-2.0.html

## Description

The plugin does two things:

1. **Adds Klingon to the WordPress language list.** After activation, **Klingon (tlhIngan Hol)** appears in *Settings → General → Site Language*. Set the site language to Klingon and the WordPress admin will use Klingon wherever translations are available.
2. **Provides WooCommerce translations in Klingon.** The plugin bundles `.mo`, `.po`, `.l10n.php`, and per-script `.json` translation files covering common WooCommerce strings — cart, checkout, order statuses, billing, shipping — across both PHP and JavaScript surfaces.

### How it works

- On **activation**, a minimal stub `tlh.mo` is copied to `wp-content/languages/tlh.mo` so WordPress recognises the locale even before filters run. All bundled `woocommerce-tlh*.{mo,po,json,php}` files are mirrored into `wp-content/languages/plugins/` so they load on the very first request.
- The `get_available_languages` filter injects `tlh` into every language list WordPress builds in the admin.
- `translations_api_result`, `pre_set_site_transient_available_translations`, and `site_transient_available_translations` together inject the human-readable label *"Klingon (tlhIngan Hol)"* so the dropdown doesn't show the bare locale code. Coverage is symmetric across the cache-hit, cache-miss, and cache-write paths inside `wp_get_available_translations()`.
- `load_textdomain_mofile` and `load_translation_file` (WP 6.5+) redirect WooCommerce's translation loading to the bundled files when the site locale is `tlh`. The `.l10n.php` PHP-cache format is preferred over `.mo` when both exist (faster load).
- `load_script_translation_file` redirects WooCommerce's per-script JS translations to the bundled `woocommerce-tlh-{md5}.json` files. Without this, anything rendered in JavaScript (Cart/Checkout blocks, admin React panels) would stay in English even when PHP strings translate correctly.
- On **deactivation**, the stub core file (`tlh.mo`) is removed. Translation files inside `wp-content/languages/plugins/` are left in place to avoid surprising the site owner during a deactivate/reactivate cycle.

### Language files

| File | Purpose |
|------|---------|
| `languages/woocommerce-tlh.po` | Human-editable source strings |
| `languages/woocommerce-tlh.mo` | Compiled binary loaded by WordPress |
| `languages/woocommerce-tlh.l10n.php` | PHP-cache format (WP 6.5+, faster than `.mo`) |
| `languages/woocommerce-tlh-{md5}.json` | Per-script JS translations for JavaScript UI |
| `languages/stub/tlh.mo` | Minimal core stub (copied to `WP_LANG_DIR` on activation) |

### Extending or improving translations

Open `languages/woocommerce-tlh.po` in [Poedit](https://poedit.net/) (free), edit the `msgstr` values, then use **File → Compile to MO** to regenerate `woocommerce-tlh.mo`. To regenerate the `.l10n.php` and per-script `.json` files, use WP-CLI:

```sh
wp i18n make-php languages/woocommerce-tlh.po
wp i18n make-json languages/woocommerce-tlh.po
```

Klingon vocabulary used in this translation:

- `Huch` — money
- `ra'` — order / command
- `leng` — journey / shipping
- `chel` — add
- `legh` — see / view
- `naQ` — complete / total
- `mI'` — number / quantity
- `nIn` — goods / product
- `mev` — stop / cancel

For authoritative Klingon, consult the [Klingon Language Institute](https://www.kli.org/).

## Installation

1. Upload the `klingon-locale` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → General** and select **Klingon (tlhIngan Hol)** from the *Site Language* dropdown.
4. Save Changes.

WooCommerce will now load Klingon strings automatically — both the PHP-rendered admin and the JavaScript-rendered storefront UI.

## Frequently Asked Questions

### Will other plugins be translated too?

No — only WooCommerce strings are covered. To add translations for other plugins, drop their `.mo` / `.l10n.php` / `.json` files into the `languages/` folder and extend the relevant filters in `klingon-for-woocommerce.php` (`load_textdomain_mofile`, `load_translation_file`, `load_script_translation_file`).

### The dropdown still shows "tlh" — what happened?

The translation-metadata injection runs through WordPress's `available_translations` site transient. If you see the bare `tlh` label, the transient was likely populated before the plugin loaded. Fix:

```sh
wp transient delete available_translations
```

Then reload *Settings → General*.

### Why are there separate `.mo`, `.l10n.php`, and `.json` files?

Each format covers a different surface:

- `.mo` is the classic compiled binary used by `__()`, `_e()`, etc., in PHP.
- `.l10n.php` is a PHP-array cache introduced in WP 6.5 — same content, faster to load than parsing a `.mo`.
- `.json` files are loaded by `wp.i18n` in the browser to translate strings rendered by JavaScript (Cart/Checkout blocks, admin React UI). PHP-only translations leave JS surfaces in English.

### Can I use this alongside a multilingual plugin?

Yes. The plugin only sets up the locale and translation files; switching logic is left to the site owner or a multilingual plugin.

## Changelog

### 1.0.0
- Initial release.
- Adds `tlh` to the WordPress language list with the human-readable label *"Klingon (tlhIngan Hol)"*.
- Bundles WooCommerce translations in `.mo`, `.po`, `.l10n.php`, and per-script `.json` formats.
- Hooks `load_textdomain_mofile`, `load_translation_file` (WP 6.5+), and `load_script_translation_file` to serve the bundled files at runtime.
