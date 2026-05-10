<?php
/**
 * Plugin Name:       Klingon for WooCommerce
 * Plugin URI:        https://github.com/RiaanKnoetze/klingon-for-woocommerce
 * Description:       Adds Klingon (tlhIngan Hol) as a selectable language in WordPress Settings, and provides WooCommerce translations in Klingon.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Riaan Knoetze
 * Text Domain:       klingon-locale
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

class Klingon_Locale {

	const LOCALE   = 'tlh';
	const WC_DOMAIN = 'woocommerce';

	public function __construct() {
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

		add_filter( 'get_available_languages',             [ $this, 'add_klingon_to_language_list' ],     10, 2 );
		add_filter( 'translations_api_result',                  [ $this, 'add_klingon_translation_metadata' ], 10, 3 );
		add_filter( 'site_transient_available_translations',    [ $this, 'inject_klingon_translation' ] );
		add_filter( 'pre_set_site_transient_available_translations', [ $this, 'inject_klingon_translation' ] );
		add_filter( 'load_textdomain_mofile',              [ $this, 'load_woocommerce_klingon' ],         10, 2 );
		add_filter( 'load_translation_file',               [ $this, 'load_woocommerce_klingon_translation' ], 10, 3 );
		add_filter( 'load_script_translation_file',        [ $this, 'load_woocommerce_klingon_script' ],  10, 3 );
	}

	// -------------------------------------------------------------------------
	// Activation / Deactivation
	// -------------------------------------------------------------------------

	/**
	 * On activation, copy a minimal stub .mo into wp-content/languages/ so
	 * WordPress includes "tlh" when it scans that directory.
	 * The get_available_languages filter handles the same job at runtime, but
	 * placing a real file means the locale persists even when the filter hasn't
	 * fired yet (e.g. during WP-CLI runs).
	 */
	public function activate() {
		$stub_source = plugin_dir_path( __FILE__ ) . 'languages/stub/tlh.mo';
		$stub_dest   = WP_LANG_DIR . '/tlh.mo';

		if ( ! file_exists( $stub_dest ) && file_exists( $stub_source ) ) {
			copy( $stub_source, $stub_dest );
		}

		// Mirror every WooCommerce translation asset into wp-content/languages/plugins/
		// so they load even before our runtime filters fire (e.g. WP-CLI, fresh requests).
		// Covers: .mo, .po, .l10n.php (WP 6.5+), and per-script .json files.
		$src_dir = plugin_dir_path( __FILE__ ) . 'languages/';
		$dst_dir = trailingslashit( WP_LANG_DIR ) . 'plugins/';

		if ( ! is_dir( $dst_dir ) ) {
			wp_mkdir_p( $dst_dir );
		}

		foreach ( glob( $src_dir . 'woocommerce-tlh*.{mo,po,json,php}', GLOB_BRACE ) as $src ) {
			$dst = $dst_dir . basename( $src );
			if ( ! file_exists( $dst ) ) {
				@copy( $src, $dst );
			}
		}
	}

	/**
	 * On deactivation, clean up the stub core file we placed.
	 * We leave the WooCommerce language file in place; removing it here could
	 * surprise the site owner if they deactivate/reactivate.
	 */
	public function deactivate() {
		$stub = WP_LANG_DIR . '/tlh.mo';
		if ( file_exists( $stub ) ) {
			unlink( $stub );
		}
	}

	// -------------------------------------------------------------------------
	// Language list
	// -------------------------------------------------------------------------

	/**
	 * Inject 'tlh' into whichever language-list WordPress is building.
	 * This covers Settings > General, Network Settings, and any call to
	 * get_available_languages() throughout the admin.
	 *
	 * @param string[]    $languages  Already-discovered locale codes.
	 * @param string|null $dir        The directory that was scanned (may be null).
	 * @return string[]
	 */
	public function add_klingon_to_language_list( array $languages, $dir = null ): array {
		if ( ! in_array( self::LOCALE, $languages, true ) ) {
			$languages[] = self::LOCALE;
		}
		return $languages;
	}

	/**
	 * Inject Klingon metadata into the translations API result so the language
	 * dropdown shows a proper name instead of the raw "tlh" locale code.
	 *
	 * Hooked on `translations_api_result` (fires after the HTTP call, value is
	 * the real API response). The cache-miss path of wp_get_available_translations()
	 * uses this directly and then writes it to the site transient, so Klingon
	 * persists in the cache for subsequent renders.
	 *
	 * @param array|WP_Error $result Translations API result.
	 * @param string         $type   API type ('core', 'plugins', 'themes').
	 * @param object         $args   API call arguments.
	 * @return array|WP_Error
	 */
	public function add_klingon_translation_metadata( $result, $type, $args ) {
		if ( 'core' !== $type || ! is_array( $result ) || empty( $result['translations'] ) ) {
			return $result;
		}

		foreach ( $result['translations'] as $translation ) {
			if ( isset( $translation['language'] ) && self::LOCALE === $translation['language'] ) {
				return $result;
			}
		}

		$result['translations'][] = [
			'language'     => self::LOCALE,
			'version'      => get_bloginfo( 'version' ),
			'updated'      => '',
			'english_name' => 'Klingon',
			'native_name'  => 'Klingon (tlhIngan Hol)',
			'package'      => '',
			'iso'          => [ 'tlh' ],
			'strings'      => [ 'continue' => 'taH' ],
		];

		return $result;
	}

	/**
	 * Inject Klingon into the cached available_translations site transient.
	 * wp_dropdown_languages() reads this array (keyed by locale) to look up
	 * the english_name / native_name shown in the Site Language dropdown.
	 *
	 * @param mixed $translations Cached translations array, or false if not cached.
	 * @return mixed
	 */
	public function inject_klingon_translation( $translations ) {
		if ( ! is_array( $translations ) ) {
			return $translations;
		}

		if ( ! isset( $translations[ self::LOCALE ] ) ) {
			$translations[ self::LOCALE ] = [
				'language'     => self::LOCALE,
				'version'      => get_bloginfo( 'version' ),
				'updated'      => '',
				'english_name' => 'Klingon',
				'native_name'  => 'Klingon (tlhIngan Hol)',
				'package'      => '',
				'iso'          => [ 'tlh' ],
				'strings'      => [ 'continue' => 'taH' ],
			];
		}

		return $translations;
	}

	// -------------------------------------------------------------------------
	// WooCommerce translations
	// -------------------------------------------------------------------------

	/**
	 * When WooCommerce (or anything else loading the 'woocommerce' text domain)
	 * tries to read a .mo file and the site locale is Klingon, redirect the
	 * load to our bundled translation instead.
	 *
	 * WordPress looks for plugin translations in (in order):
	 *   1. wp-content/languages/plugins/woocommerce-{locale}.mo  (user-managed)
	 *   2. The plugin's own /languages/ directory
	 *
	 * We hook here so our bundled file is always used, even if WooCommerce
	 * itself ships an empty or missing tlh.mo at some point in the future.
	 *
	 * @param string $mofile  Full path WordPress is about to load.
	 * @param string $domain  Text domain being loaded.
	 * @return string
	 */
	public function load_woocommerce_klingon( string $mofile, string $domain ): string {
		if ( self::WC_DOMAIN !== $domain ) {
			return $mofile;
		}

		if ( get_locale() !== self::LOCALE ) {
			return $mofile;
		}

		$bundled = plugin_dir_path( __FILE__ ) . 'languages/woocommerce-tlh.mo';

		return file_exists( $bundled ) ? $bundled : $mofile;
	}

	/**
	 * WP 6.5+ load_translation_file filter: covers both .mo and .l10n.php formats.
	 * Fires after load_textdomain_mofile and is the canonical hook for redirecting
	 * the binary (or PHP-cache) translation file. We prefer .l10n.php when present
	 * because WP loads it faster than parsing a .mo.
	 *
	 * @param string $file   Path WordPress is about to load.
	 * @param string $domain Text domain.
	 * @param string $locale Locale being loaded.
	 * @return string
	 */
	public function load_woocommerce_klingon_translation( string $file, string $domain, string $locale ): string {
		if ( self::WC_DOMAIN !== $domain || self::LOCALE !== $locale ) {
			return $file;
		}

		$lang_dir = plugin_dir_path( __FILE__ ) . 'languages/';

		$php = $lang_dir . 'woocommerce-tlh.l10n.php';
		if ( file_exists( $php ) ) {
			return $php;
		}

		$mo = $lang_dir . 'woocommerce-tlh.mo';
		if ( file_exists( $mo ) ) {
			return $mo;
		}

		return $file;
	}

	/**
	 * Redirect WooCommerce JS script translations (per-handle .json files) to our
	 * bundled copies when the locale is Klingon. WP looks up files by an md5 of
	 * the script's source path; the filename WP requests is what we look for in
	 * our /languages/ directory.
	 *
	 * @param string|false $file   Path WordPress is about to load (false if not found).
	 * @param string       $handle Script handle.
	 * @param string       $domain Text domain.
	 * @return string|false
	 */
	public function load_woocommerce_klingon_script( $file, string $handle, string $domain ) {
		if ( self::WC_DOMAIN !== $domain || get_locale() !== self::LOCALE ) {
			return $file;
		}

		$basename = is_string( $file ) ? basename( $file ) : '';
		if ( '' === $basename ) {
			return $file;
		}

		$bundled = plugin_dir_path( __FILE__ ) . 'languages/' . $basename;

		return file_exists( $bundled ) ? $bundled : $file;
	}
}

new Klingon_Locale();
