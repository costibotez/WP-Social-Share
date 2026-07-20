<?php
/**
 * Uninstall cleanup for TopTal Social Share.
 *
 * Removes every option the plugin registers (including the per-network
 * color options) and the per-post share-count meta.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$toptal_ss_options = array(
	// General settings.
	'toptal_ss_facebook',
	'toptal_ss_twitter',
	'toptal_ss_linkedin',
	'toptal_ss_pinterest',
	'toptal_ss_whatsapp',
	// Visibility settings.
	'toptal_ss_posts',
	'toptal_ss_page',
	'toptal_ss_cpt',
	// Locations settings.
	'toptal_ss_below_post_title',
	'toptal_ss_left_area',
	'toptal_ss_after_post_content',
	'toptal_ss_inside_featured_image',
	// Size, appearance and color settings.
	'toptal_ss_size',
	'toptal_ss_appearance',
	'toptal_ss_color',
	// Custom color settings.
	'toptal_ss_facebook_color',
	'toptal_ss_facebook_bk_color',
	'toptal_ss_twitter_color',
	'toptal_ss_twitter_bk_color',
	'toptal_ss_linkedin_color',
	'toptal_ss_linkedin_bk_color',
	'toptal_ss_pinterest_color',
	'toptal_ss_pinterest_bk_color',
	'toptal_ss_whatsapp_color',
	'toptal_ss_whatsapp_bk_color',
);

foreach ( $toptal_ss_options as $toptal_ss_option ) {
	delete_option( $toptal_ss_option );
}

delete_post_meta_by_key( 'toptal_ss_share_counts' );
