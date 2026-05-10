=== Klingon for WooCommerce ===
Contributors: riaanknoetze
Tags: klingon, language, locale, woocommerce, i18n
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds Klingon (tlhIngan Hol) as a selectable WordPress language with full translations for WordPress core and WooCommerce, across PHP and JavaScript UI.

== Description ==

Klingon for WooCommerce registers **Klingon (tlhIngan Hol)** as a selectable language under *Settings > General > Site Language* and bundles a full set of WordPress core translations (`default`, `admin`, `admin-network` domains — ~11,500 strings) plus WooCommerce translations, covering both PHP-rendered pages and JavaScript-rendered UI (Block Editor, Site Editor, Cart/Checkout blocks, admin React panels).

= Features =

* Adds the `tlh` locale to every WordPress language dropdown in the admin.
* Displays the human-readable label "Klingon (tlhIngan Hol)" instead of the bare locale code.
* Translates **WordPress core** across the `default`, `admin`, and `admin-network` text domains (~11,500 strings).
* Translates **WooCommerce** across the `woocommerce` text domain.
* Ships translations in four complementary formats:
  * `.mo` — classic compiled binary for `__()`, `_e()`, etc.
  * `.l10n.php` — PHP-cache format (WP 6.5+, faster than `.mo`)
  * `.po` — human-editable source for translators
  * `woocommerce-tlh-{md5}.json` — per-script translations for JavaScript UI
* Mirrors all bundled translation files into `wp-content/languages/plugins/` on activation so they load on the very first request — even before any runtime filter fires.
* Prefers `.l10n.php` over `.mo` at load time when both are present.
* Preserves the WooCommerce translation in `wp-content/languages/plugins/` on deactivation to avoid surprising users during deactivate/reactivate cycles.

= How it works =

The plugin uses several WordPress filters working together:

* `get_available_languages` — injects `tlh` into the available-locales list everywhere in the admin.
* `translations_api_result`, `pre_set_site_transient_available_translations`, and `site_transient_available_translations` — inject the "Klingon (tlhIngan Hol)" metadata symmetrically across the cache-hit, cache-miss, and cache-write paths of `wp_get_available_translations()`, so the dropdown label sticks regardless of transient state.
* `load_textdomain_mofile` and `load_translation_file` (WP 6.5+) — redirect WooCommerce's translation loading to the bundled files when the site locale is `tlh`.
* `load_script_translation_file` — redirects WooCommerce's per-script JSON translations so JavaScript UI (Cart/Checkout blocks, admin React) is translated alongside the PHP surfaces.

= Klingon vocabulary =

A few of the terms used in this translation:

* `Huch` — money
* `ra'` — order / command
* `leng` — journey / shipping
* `chel` — add
* `legh` — see / view
* `naQ` — complete / total
* `mI'` — number / quantity
* `nIn` — goods / product
* `mev` — stop / cancel

For authoritative Klingon, consult the [Klingon Language Institute](https://www.kli.org/).

== Installation ==

1. Upload the `klingon-locale` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings > General** and select **Klingon (tlhIngan Hol)** from the *Site Language* dropdown.
4. Save Changes.

WooCommerce will now load Klingon strings automatically — both the PHP admin and the JavaScript-rendered storefront UI.

== Frequently Asked Questions ==

= Will other plugins be translated too? =

WordPress core (default/admin/admin-network) and WooCommerce are bundled. Other plugins aren't — but adding them is straightforward: drop their translation files into the `languages/` folder and add an entry to the `PLUGIN_DOMAINS` map at the top of `klingon-for-woocommerce.php`. The runtime filters and activation copy logic pick up new domains automatically.

= The dropdown still shows "tlh" — what happened? =

The translation-metadata injection runs through WordPress's `available_translations` site transient. If you see the bare `tlh` label, the transient was likely populated before the plugin loaded. Clear it with WP-CLI:

`wp transient delete available_translations`

Then reload *Settings > General*.

= Why are there separate .mo, .l10n.php, and .json files? =

Each format covers a different surface:

* `.mo` is the classic compiled binary used by `__()`, `_e()`, etc., in PHP.
* `.l10n.php` is a PHP-array cache introduced in WP 6.5 — same content, faster to load than parsing a `.mo`.
* `.json` files are loaded by `wp.i18n` in the browser to translate strings rendered by JavaScript (Cart/Checkout blocks, admin React UI). PHP-only translations leave JS surfaces in English.

= How do I extend or improve the Klingon translations? =

Open `languages/woocommerce-tlh.po` in [Poedit](https://poedit.net/) and edit the `msgstr` values, then use **File > Compile to MO** to regenerate `woocommerce-tlh.mo`. To regenerate the `.l10n.php` and per-script `.json` files, run:

`wp i18n make-php languages/woocommerce-tlh.po`
`wp i18n make-json languages/woocommerce-tlh.po`

= Can I use this alongside a multilingual plugin? =

Yes. The plugin only sets up the locale and translation files; switching logic is left to the site owner or a multilingual plugin.

== Changelog ==

= 1.0.0 =
* Initial release.
* Adds `tlh` to the WordPress language list with the human-readable label "Klingon (tlhIngan Hol)".
* Bundles WordPress core translations (`default`, `admin`, `admin-network` domains) and WooCommerce translations in `.mo`, `.po`, `.l10n.php`, and per-script `.json` formats.
* Hooks `load_textdomain_mofile`, `load_translation_file` (WP 6.5+), and `load_script_translation_file` to serve the bundled files at runtime across all four domains.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
