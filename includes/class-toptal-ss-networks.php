<?php
declare(strict_types=1);
/**
 * Network registry: every supported network with its label, icon and
 * share endpoint. Adding a network here is all that's needed for it to
 * appear in the settings screen, the renderer and the AJAX whitelist.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TopTal_SS_Networks {

	/**
	 * @return array<string, array{label: string, icon: string, share_url: string, mobile_only?: bool}>
	 */
	public static function all(): array {
		$networks = array(
			'facebook'  => array(
				'label'     => __( 'Facebook', 'toptal-ss' ),
				'icon'      => 'fa-facebook',
				'share_url' => 'https://www.facebook.com/sharer/sharer.php?u=%s',
			),
			'twitter'   => array(
				'label'     => __( 'Twitter', 'toptal-ss' ),
				'icon'      => 'fa-twitter',
				'share_url' => 'https://twitter.com/intent/tweet?url=%s',
			),
			'linkedin'  => array(
				'label'     => __( 'LinkedIn', 'toptal-ss' ),
				'icon'      => 'fa-linkedin',
				'share_url' => 'https://www.linkedin.com/shareArticle?mini=true&url=%s',
			),
			'pinterest' => array(
				'label'     => __( 'Pinterest', 'toptal-ss' ),
				'icon'      => 'fa-pinterest',
				'share_url' => 'https://pinterest.com/pin/create/button/?url=%s',
			),
			'whatsapp'  => array(
				'label'       => __( 'WhatsApp', 'toptal-ss' ),
				'icon'        => 'fa-whatsapp',
				'share_url'   => 'https://api.whatsapp.com/send?text=%s',
				'mobile_only' => true,
			),
		);

		return apply_filters( 'toptal_ss_networks', $networks );
	}

	/** @return string[] */
	public static function keys(): array {
		return array_keys( self::all() );
	}

	public static function share_url( string $key, string $permalink ): string {
		$networks = self::all();
		if ( ! isset( $networks[ $key ] ) ) {
			return '';
		}
		return sprintf( $networks[ $key ]['share_url'], rawurlencode( $permalink ) );
	}
}
