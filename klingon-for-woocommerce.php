<?php
/**
 * Plugin Name:       Klingon for WooCommerce
 * Plugin URI:        https://github.com/RiaanKnoetze/klingon-for-woocommerce
 * Description:       Adds Klingon (tlhIngan Hol) as a selectable language in WordPress, with translations for WordPress core and WooCommerce.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Riaan Knoetze
 * Text Domain:       klingon-for-woocommerce
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

class Klingon_Locale {

	const LOCALE = 'tlh';

	/**
	 * Map of text domains we provide translations for, to the filename prefix
	 * WordPress expects for each in `wp-content/languages/` (or `plugins/`).
	 *
	 * - 'default' (core) lives at WP_LANG_DIR/{locale}.mo (no prefix).
	 * - 'admin'         at WP_LANG_DIR/admin-{locale}.mo
	 * - 'admin-network' at WP_LANG_DIR/admin-network-{locale}.mo
	 * - 'woocommerce'   at WP_LANG_DIR/plugins/woocommerce-{locale}.mo
	 */
	const CORE_DOMAINS = [
		'default'       => '',
		'admin'         => 'admin-',
		'admin-network' => 'admin-network-',
	];

	const PLUGIN_DOMAINS = [
		'woocommerce' => 'woocommerce-',
	];

	public function __construct() {
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

		add_filter( 'get_available_languages',                       [ $this, 'add_klingon_to_language_list' ],     10, 2 );
		add_filter( 'translations_api_result',                       [ $this, 'add_klingon_translation_metadata' ], 10, 3 );
		add_filter( 'site_transient_available_translations',         [ $this, 'inject_klingon_translation' ] );
		add_filter( 'pre_set_site_transient_available_translations', [ $this, 'inject_klingon_translation' ] );
		add_filter( 'load_textdomain_mofile',                        [ $this, 'load_klingon_mofile' ],              10, 2 );
		add_filter( 'load_translation_file',                         [ $this, 'load_klingon_translation' ],         10, 3 );
		add_filter( 'load_script_translation_file',                  [ $this, 'load_klingon_script' ],              10, 3 );
	}

	// -------------------------------------------------------------------------
	// Activation / Deactivation
	// -------------------------------------------------------------------------

	/**
	 * Mirror bundled translations into the locations WordPress checks natively,
	 * so files load on the very first request — even before our runtime filters
	 * fire (e.g. WP-CLI, fresh requests, multisite locale switches).
	 *
	 * Core domains land in WP_LANG_DIR; plugin domains in WP_LANG_DIR/plugins/.
	 */
	public function activate() {
		$src_dir   = plugin_dir_path( __FILE__ ) . 'languages/';
		$core_dir  = trailingslashit( WP_LANG_DIR );
		$plugin_dir = $core_dir . 'plugins/';

		if ( ! is_dir( $plugin_dir ) ) {
			wp_mkdir_p( $plugin_dir );
		}

		$copy = static function ( $pattern, $dst_dir ) use ( $src_dir ) {
			$matches = glob( $src_dir . $pattern, GLOB_BRACE );
			if ( ! $matches ) {
				return;
			}
			foreach ( $matches as $src ) {
				$dst = $dst_dir . basename( $src );
				if ( ! file_exists( $dst ) ) {
					@copy( $src, $dst );
				}
			}
		};

		// Core domains: tlh.*, admin-tlh.*, admin-network-tlh.* and their per-script JSON.
		foreach ( self::CORE_DOMAINS as $prefix ) {
			$copy( $prefix . self::LOCALE . '*.{mo,po,json,php}', $core_dir );
		}

		// Plugin domains (WooCommerce): woocommerce-tlh.* and per-script JSON.
		foreach ( self::PLUGIN_DOMAINS as $prefix ) {
			$copy( $prefix . self::LOCALE . '*.{mo,po,json,php}', $plugin_dir );
		}
	}

	/**
	 * On deactivation, remove the core-domain files we placed so WordPress
	 * stops claiming Klingon translations exist when the plugin isn't active.
	 * Plugin-domain files in WP_LANG_DIR/plugins/ are left in place to avoid
	 * surprising the site owner during a deactivate/reactivate cycle.
	 */
	public function deactivate() {
		$core_dir = trailingslashit( WP_LANG_DIR );

		foreach ( self::CORE_DOMAINS as $prefix ) {
			foreach ( glob( $core_dir . $prefix . self::LOCALE . '*.{mo,po,json,php}', GLOB_BRACE ) as $file ) {
				@unlink( $file );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Language list
	// -------------------------------------------------------------------------

	/**
	 * Inject 'tlh' into whichever language-list WordPress is building.
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
	 * Hooked on `translations_api_result` (fires after the HTTP call). The
	 * cache-miss path of wp_get_available_translations() uses this directly
	 * and then writes it to the site transient, so Klingon persists in cache
	 * for subsequent renders.
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

		$result['translations'][] = $this->klingon_translation_entry();

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
			$translations[ self::LOCALE ] = $this->klingon_translation_entry();
		}

		return $translations;
	}

	private function klingon_translation_entry(): array {
		return [
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

	// -------------------------------------------------------------------------
	// Translation file routing
	// -------------------------------------------------------------------------

	/**
	 * Resolve the bundled path for a given text domain, preferring .l10n.php
	 * (PHP-cache, WP 6.5+) over .mo when both exist.
	 *
	 * @param string $domain Text domain (must be a known core or plugin domain).
	 * @return string|null Full path to the bundled file, or null if none found.
	 */
	private function bundled_path_for_domain( string $domain ): ?string {
		$prefix = self::CORE_DOMAINS[ $domain ] ?? self::PLUGIN_DOMAINS[ $domain ] ?? null;
		if ( null === $prefix ) {
			return null;
		}

		$lang_dir = plugin_dir_path( __FILE__ ) . 'languages/';
		$base     = $lang_dir . $prefix . self::LOCALE;

		if ( file_exists( $base . '.l10n.php' ) ) {
			return $base . '.l10n.php';
		}
		if ( file_exists( $base . '.mo' ) ) {
			return $base . '.mo';
		}
		return null;
	}

	/**
	 * Legacy `load_textdomain_mofile` filter — used by older WP versions and
	 * code paths that pre-date `load_translation_file`. Still useful as a
	 * belt-and-braces fallback.
	 *
	 * @param string $mofile Path WP is about to load.
	 * @param string $domain Text domain being loaded.
	 * @return string
	 */
	public function load_klingon_mofile( string $mofile, string $domain ): string {
		if ( get_locale() !== self::LOCALE ) {
			return $mofile;
		}

		$bundled = $this->bundled_path_for_domain( $domain );
		if ( null === $bundled ) {
			return $mofile;
		}

		// load_textdomain_mofile expects a .mo path specifically.
		if ( substr( $bundled, -3 ) === '.mo' ) {
			return $bundled;
		}

		$mo = substr( $bundled, 0, -9 ) . '.mo'; // swap .l10n.php → .mo
		return file_exists( $mo ) ? $mo : $mofile;
	}

	/**
	 * WP 6.5+ `load_translation_file` filter — canonical hook for redirecting
	 * the binary (.mo) or PHP-cache (.l10n.php) translation file.
	 *
	 * @param string $file   Path WordPress is about to load.
	 * @param string $domain Text domain.
	 * @param string $locale Locale being loaded.
	 * @return string
	 */
	public function load_klingon_translation( string $file, string $domain, string $locale ): string {
		if ( self::LOCALE !== $locale ) {
			return $file;
		}

		$bundled = $this->bundled_path_for_domain( $domain );
		return $bundled ?? $file;
	}

	/**
	 * Redirect per-script JSON translations to our bundled copies when the
	 * locale is Klingon and the domain is one we cover. WP looks up files by
	 * an md5 of the script's source path — the filename WP requests is what we
	 * look for in our /languages/ directory.
	 *
	 * @param string|false $file   Path WordPress is about to load (false if not found).
	 * @param string       $handle Script handle.
	 * @param string       $domain Text domain.
	 * @return string|false
	 */
	public function load_klingon_script( $file, string $handle, string $domain ) {
		if ( get_locale() !== self::LOCALE ) {
			return $file;
		}

		if ( ! isset( self::CORE_DOMAINS[ $domain ] ) && ! isset( self::PLUGIN_DOMAINS[ $domain ] ) ) {
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
