<?php
/**
 * Registers and conditionally enqueues frontend and admin assets.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TopTal_SS_Assets {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	public function register_frontend(): void {
		wp_register_style( 'toptal-ss-style', TOPTAL_SS_PLUGIN_DIR_ASSETS_URL . 'css/style.css', array(), TOPTAL_SS_VERSION );
		wp_register_style( 'toptal-ss-fontawesome', TOPTAL_SS_PLUGIN_DIR_ASSETS_URL . 'css/font-awesome.min.css', array(), TOPTAL_SS_VERSION );
		wp_register_script( 'toptal-ss-share-counts', TOPTAL_SS_PLUGIN_DIR_ASSETS_URL . 'js/share-counts.js', array(), TOPTAL_SS_VERSION, true );
		wp_localize_script(
			'toptal-ss-share-counts',
			'toptalShareCount',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'toptal_ss_share_count' ),
			)
		);

		if ( $this->should_enqueue() ) {
			self::enqueue_frontend();
		}
	}

	/**
	 * Enqueue the registered frontend assets. Also called from the renderer
	 * so shortcode/widget usage outside the detected locations still gets
	 * styles and the share-count script (they land in the footer then).
	 */
	public static function enqueue_frontend(): void {
		if ( ! wp_style_is( 'toptal-ss-style', 'registered' ) ) {
			return;
		}
		wp_enqueue_style( 'toptal-ss-style' );
		wp_enqueue_style( 'toptal-ss-fontawesome' );
		wp_enqueue_script( 'toptal-ss-share-counts' );
	}

	private function should_enqueue(): bool {
		if ( ! is_singular() ) {
			return false;
		}

		$settings = TopTal_SS_Options::get();
		if ( ! in_array( 1, array_map( 'intval', $settings['networks'] ), true ) ) {
			return false;
		}

		$locations_active = in_array( 1, array_map( 'intval', $settings['locations'] ), true );
		if ( $locations_active && TopTal_SS_Renderer::post_type_enabled( get_post_type() ) ) {
			return true;
		}

		$post = get_post();
		if ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'toptal_ss' ) ) {
			return true;
		}

		return false;
	}

	public function enqueue_admin( string $hook ): void {
		if ( 'settings_page_toptal_social_share' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'toptal-ss-admin', TOPTAL_SS_PLUGIN_DIR_ASSETS_URL . 'css/admin.css', array(), TOPTAL_SS_VERSION );
		wp_enqueue_script( 'toptal-ss-admin', TOPTAL_SS_PLUGIN_DIR_ASSETS_URL . 'js/scripts.js', array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ), TOPTAL_SS_VERSION, true );
	}
}
