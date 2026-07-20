<?php
declare(strict_types=1);
/**
 * AJAX endpoint that records share-button clicks in post meta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TopTal_SS_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_toptal_update_share_count', array( $this, 'update_share_count' ) );
		add_action( 'wp_ajax_nopriv_toptal_update_share_count', array( $this, 'update_share_count' ) );
	}

	public function update_share_count(): void {
		check_ajax_referer( 'toptal_ss_share_count', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$network = isset( $_POST['network'] ) ? sanitize_key( (string) $_POST['network'] ) : '';
		if ( ! $post_id || ! in_array( $network, TopTal_SS_Networks::keys(), true ) ) {
			wp_send_json_error( null, 400 );
		}

		// Only count shares of published, publicly viewable posts.
		if ( get_post_status( $post_id ) !== 'publish' || ! is_post_type_viewable( get_post_type( $post_id ) ) ) {
			wp_send_json_error( null, 404 );
		}

		// Throttle: one increment per client / post / network per minute.
		$ip           = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		$throttle_key = 'toptal_ss_tt_' . md5( $ip . '|' . $post_id . '|' . $network );
		if ( get_transient( $throttle_key ) ) {
			wp_send_json_error( null, 429 );
		}
		set_transient( $throttle_key, 1, MINUTE_IN_SECONDS );

		$counts = get_post_meta( $post_id, 'toptal_ss_share_counts', true );
		if ( ! is_array( $counts ) ) {
			$counts = array();
		}
		$counts[ $network ] = intval( $counts[ $network ] ?? 0 ) + 1;
		$counts['total']    = intval( $counts['total'] ?? 0 ) + 1;

		update_post_meta( $post_id, 'toptal_ss_share_counts', $counts );
		wp_send_json_success( $counts[ $network ] );
	}
}
