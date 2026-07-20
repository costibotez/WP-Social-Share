<?php
/**
 * Frontend rendering: builds the share-button markup and injects it via
 * the content/title/thumbnail filters, the floating bar and the shortcode.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TopTal_SS_Renderer {

	public function __construct() {
		add_filter( 'the_content', array( $this, 'inject_after_content' ) );
		add_filter( 'the_title', array( $this, 'inject_below_title' ), 99, 1 );
		add_filter( 'post_thumbnail_html', array( $this, 'inject_inside_featured_image' ), 99, 2 );
		add_action( 'wp_footer', array( $this, 'render_float_area' ), 10 );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
		add_shortcode( 'toptal_ss', array( $this, 'shortcode' ) );
	}

	public static function post_type_enabled( ?string $post_type ): bool {
		$settings = TopTal_SS_Options::get();

		if ( 'post' === $post_type ) {
			return intval( $settings['post_types']['post'] ) === 1;
		}
		if ( 'page' === $post_type ) {
			return intval( $settings['post_types']['page'] ) === 1;
		}

		$builtin = array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item' );
		if ( $post_type && ! in_array( $post_type, $builtin, true ) ) {
			return intval( $settings['post_types']['cpt'] ) === 1;
		}

		return false;
	}

	public function add_body_class( array $body_class ): array {
		$settings = TopTal_SS_Options::get();
		if ( intval( $settings['locations']['left_area'] ) === 1 ) {
			$body_class[] = 'toptal-ss-left-area';
		}
		return $body_class;
	}

	public function inject_after_content( $content ) {
		global $post;
		if ( ! $post instanceof WP_Post || ! self::post_type_enabled( get_post_type( $post ) ) ) {
			return $content;
		}

		$settings = TopTal_SS_Options::get();
		if ( intval( $settings['locations']['after_post_content'] ) === 1 ) {
			$content .= $this->buttons_html( $post->ID );
		}

		return $content;
	}

	public function inject_below_title( $title ) {
		global $post;
		if ( ! $post instanceof WP_Post || ! in_the_loop() || ! self::post_type_enabled( get_post_type( $post ) ) ) {
			return $title;
		}

		$settings = TopTal_SS_Options::get();
		if ( intval( $settings['locations']['below_post_title'] ) === 1 ) {
			$title .= $this->buttons_html( $post->ID );
		}

		return $title;
	}

	public function inject_inside_featured_image( $html_image, $post_id ) {
		global $post;
		if ( ! $post instanceof WP_Post || ! in_the_loop() || ! self::post_type_enabled( get_post_type( $post ) ) ) {
			return $html_image;
		}

		$settings = TopTal_SS_Options::get();
		if ( intval( $settings['locations']['inside_featured_image'] ) === 1 ) {
			$html_image .= $this->buttons_html( (int) $post_id );
		}

		return $html_image;
	}

	public function render_float_area(): void {
		$settings = TopTal_SS_Options::get();
		if ( intval( $settings['locations']['left_area'] ) !== 1 ) {
			return;
		}
		if ( ! is_singular() || ! self::post_type_enabled( get_post_type() ) ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		echo wp_kses_post(
			str_replace(
				'toptal-social-share-wrapper',
				'toptal-social-share-wrapper float-area',
				$this->buttons_html( (int) $post_id )
			)
		);
	}

	public function shortcode( $atts ): string {
		global $post;
		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$defaults = array( 'size' => 'small' );
		foreach ( TopTal_SS_Networks::keys() as $key ) {
			$defaults[ $key ] = 0;
		}

		$a = shortcode_atts( $defaults, (array) $atts );

		$size      = strtolower( (string) $a['size'] );
		$overrides = array(
			'size'     => in_array( $size, array( 'small', 'medium', 'large' ), true ) ? $size : 'small',
			'networks' => array(),
		);
		foreach ( TopTal_SS_Networks::keys() as $key ) {
			$overrides['networks'][ $key ] = intval( $a[ $key ] ) === 1 ? 1 : 0;
		}

		return $this->buttons_html( $post->ID, $overrides );
	}

	/**
	 * Build the share-buttons markup for a post.
	 *
	 * @param int        $post_id   Post to share.
	 * @param array|null $overrides Optional ['size' => string, 'networks' => array] from the shortcode.
	 */
	public function buttons_html( int $post_id, ?array $overrides = null ): string {
		$settings   = TopTal_SS_Options::get();
		$networks   = TopTal_SS_Networks::all();
		$appearance = intval( $settings['appearance'] );
		$size       = $overrides['size'] ?? $settings['size'];
		$enabled    = $overrides['networks'] ?? $settings['networks'];

		$permalink = (string) get_permalink( $post_id );

		$counts = get_post_meta( $post_id, 'toptal_ss_share_counts', true );
		if ( ! is_array( $counts ) ) {
			$counts = array();
		}

		if ( class_exists( 'TopTal_SS_Assets' ) ) {
			TopTal_SS_Assets::enqueue_frontend();
		}

		$just_icon = ( 1 === $appearance ) ? ' just_icon' : '';
		$html      = '<div class="toptal-social-share-wrapper' . $just_icon . '" data-post-id="' . esc_attr( (string) $post_id ) . '">';

		foreach ( $networks as $key => $network ) {
			if ( intval( $enabled[ $key ] ?? 0 ) !== 1 ) {
				continue;
			}

			$share_url = TopTal_SS_Networks::share_url( $key, $permalink );
			$count     = intval( $counts[ $key ] ?? 0 );
			$style     = $this->style_attribute( $key, $settings );

			$icon  = '<span class="fa ' . esc_attr( $network['icon'] ) . ( 1 === $appearance ? ' icon' : '' ) . '"></span>';
			$label = esc_html( $network['label'] );
			switch ( $appearance ) {
				case 1:
					$inner = $icon;
					break;
				case 2:
					$inner = $label;
					break;
				default:
					$inner = $icon . $label;
			}

			$attrs = '';
			if ( ! isset( $network['new_tab'] ) || false !== $network['new_tab'] ) {
				$attrs .= ' target="_blank" rel="noopener noreferrer"';
			}
			if ( isset( $network['action'] ) && 'copy' === $network['action'] ) {
				$attrs .= ' data-toptal-action="copy"';
			}

			$html .= '<div class="' . esc_attr( $key . ' ' . $size ) . '"' . $style . '>'
				. '<a' . $attrs . ' href="' . esc_url( $share_url ) . '">' . $inner . '</a>'
				. '<span class="share-count">' . $count . '</span>'
				. '</div>';
		}

		$html .= '<div class="clear"></div></div><div class="clear"></div>';

		return $html;
	}

	private function style_attribute( string $network_key, array $settings ): string {
		if ( intval( $settings['default_colors'] ) === 1 ) {
			return '';
		}

		$colors = $settings['colors'][ $network_key ] ?? array();
		$bg     = (string) ( $colors['bg'] ?? '' );
		$text   = (string) ( $colors['text'] ?? '' );
		if ( '' === $bg && '' === $text ) {
			return '';
		}

		$rules = array();
		if ( '' !== $bg ) {
			$rules[] = 'background-color:' . $bg;
		}
		if ( '' !== $text ) {
			$rules[] = 'color:' . $text;
		}

		return ' style="' . esc_attr( implode( '; ', $rules ) ) . '"';
	}
}
