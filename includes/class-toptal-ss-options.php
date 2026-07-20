<?php
declare(strict_types=1);
/**
 * Single-option settings storage with defaults and one-time migration
 * from the legacy scalar options used by version 1.0.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TopTal_SS_Options {

	const OPTION_KEY = 'toptal_ss_settings';

	const LOCATIONS = array( 'below_post_title', 'left_area', 'after_post_content', 'inside_featured_image' );

	/** @var array|null */
	private static $cache = null;

	public static function defaults(): array {
		$network_keys = TopTal_SS_Networks::keys();

		return array(
			'networks'       => array_fill_keys( $network_keys, 0 ),
			'post_types'     => array(
				'post' => 0,
				'page' => 0,
				'cpt'  => 0,
			),
			'locations'      => array_fill_keys( self::LOCATIONS, 0 ),
			'size'           => 'small',
			'appearance'     => 3,
			'default_colors' => 1,
			'colors'         => array_fill_keys(
				$network_keys,
				array(
					'bg'   => '',
					'text' => '',
				)
			),
		);
	}

	public static function get( bool $refresh = false ): array {
		if ( null !== self::$cache && ! $refresh ) {
			return self::$cache;
		}

		$stored = get_option( self::OPTION_KEY );
		if ( false === $stored ) {
			$stored = self::migrate_legacy();
		}

		self::$cache = self::merge_defaults( is_array( $stored ) ? $stored : array() );

		return self::$cache;
	}

	public static function flush_cache(): void {
		self::$cache = null;
	}

	/**
	 * On activation, seed sensible defaults for fresh installs (or migrate
	 * a legacy install) so buttons show up without ticking every box.
	 */
	public static function install_defaults(): void {
		if ( false !== get_option( self::OPTION_KEY ) ) {
			return;
		}
		if ( false !== self::migrate_legacy() ) {
			return;
		}

		$defaults = self::defaults();

		$defaults['networks']['facebook']           = 1;
		$defaults['networks']['twitter']            = 1;
		$defaults['networks']['linkedin']           = 1;
		$defaults['post_types']['post']             = 1;
		$defaults['post_types']['page']             = 1;
		$defaults['locations']['after_post_content'] = 1;

		add_option( self::OPTION_KEY, $defaults );
		self::flush_cache();
	}

	private static function merge_defaults( array $settings ): array {
		$defaults = self::defaults();

		foreach ( array( 'networks', 'post_types', 'locations', 'colors' ) as $section ) {
			$stored_section       = ( isset( $settings[ $section ] ) && is_array( $settings[ $section ] ) ) ? $settings[ $section ] : array();
			$settings[ $section ] = array_merge( $defaults[ $section ], $stored_section );
		}

		foreach ( $defaults['colors'] as $key => $pair ) {
			$stored_pair               = ( isset( $settings['colors'][ $key ] ) && is_array( $settings['colors'][ $key ] ) ) ? $settings['colors'][ $key ] : array();
			$settings['colors'][ $key ] = array_merge( $pair, $stored_pair );
		}

		return array_merge( $defaults, $settings );
	}

	/**
	 * Build the settings array from the 1.0 scalar options, persist it and
	 * delete the legacy options. Returns false when there is nothing to migrate.
	 *
	 * @return array|false
	 */
	private static function migrate_legacy() {
		$has_legacy = false;
		foreach ( array( 'toptal_ss_facebook', 'toptal_ss_posts', 'toptal_ss_size', 'toptal_ss_appearance' ) as $probe ) {
			if ( false !== get_option( $probe ) ) {
				$has_legacy = true;
				break;
			}
		}
		if ( ! $has_legacy ) {
			return false;
		}

		$settings = self::defaults();

		foreach ( TopTal_SS_Networks::keys() as $key ) {
			$settings['networks'][ $key ] = get_option( 'toptal_ss_' . $key ) == 1 ? 1 : 0;
			$settings['colors'][ $key ]   = array(
				'bg'   => (string) get_option( 'toptal_ss_' . $key . '_bk_color', '' ),
				'text' => (string) get_option( 'toptal_ss_' . $key . '_color', '' ),
			);
		}

		$settings['post_types']['post'] = get_option( 'toptal_ss_posts' ) == 1 ? 1 : 0;
		$settings['post_types']['page'] = get_option( 'toptal_ss_page' ) == 1 ? 1 : 0;
		$settings['post_types']['cpt']  = get_option( 'toptal_ss_cpt' ) == 1 ? 1 : 0;

		foreach ( self::LOCATIONS as $location ) {
			$settings['locations'][ $location ] = get_option( 'toptal_ss_' . $location ) == 1 ? 1 : 0;
		}

		$size             = strtolower( (string) get_option( 'toptal_ss_size' ) );
		$settings['size'] = in_array( $size, array( 'small', 'medium', 'large' ), true ) ? $size : 'small';

		$appearance             = intval( get_option( 'toptal_ss_appearance' ) );
		$settings['appearance'] = ( $appearance >= 1 && $appearance <= 3 ) ? $appearance : 3;

		$settings['default_colors'] = get_option( 'toptal_ss_color' ) == 1 ? 1 : 0;

		add_option( self::OPTION_KEY, $settings );
		self::delete_legacy();
		self::flush_cache();

		return $settings;
	}

	private static function delete_legacy(): void {
		$legacy = array( 'toptal_ss_posts', 'toptal_ss_page', 'toptal_ss_cpt', 'toptal_ss_size', 'toptal_ss_appearance', 'toptal_ss_color' );
		foreach ( TopTal_SS_Networks::keys() as $key ) {
			$legacy[] = 'toptal_ss_' . $key;
			$legacy[] = 'toptal_ss_' . $key . '_color';
			$legacy[] = 'toptal_ss_' . $key . '_bk_color';
		}
		foreach ( self::LOCATIONS as $location ) {
			$legacy[] = 'toptal_ss_' . $location;
		}
		foreach ( $legacy as $option ) {
			delete_option( $option );
		}
	}
}
