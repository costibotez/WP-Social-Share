<?php
/**
 * Admin settings screen. All fields write into the single
 * `toptal_ss_settings` array option and are generated from the network
 * registry, so new networks appear automatically.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TopTal_SS_Settings {

	const PAGE = 'toptal_social_share';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . TOPTAL_SS_PLUGIN_PATH, array( $this, 'add_action_links' ) );
	}

	public function add_menu_item(): void {
		add_options_page(
			__( 'TopTal Social Share', 'toptal-ss' ),
			__( 'TopTal Social Share', 'toptal-ss' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	public function add_action_links( array $links ): array {
		$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Settings', 'toptal-ss' ) . '</a>';
		return $links;
	}

	public function render_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'TopTal Social Sharing Options', 'toptal-ss' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'toptal_ss_settings_all' );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function register_settings(): void {
		register_setting(
			'toptal_ss_settings_all',
			TopTal_SS_Options::OPTION_KEY,
			array( 'sanitize_callback' => array( $this, 'sanitize_settings' ) )
		);

		add_settings_section( 'toptal_ss_general_section', __( 'General Options', 'toptal-ss' ), null, self::PAGE );
		add_settings_section( 'toptal_ss_visibility_section', __( 'Visibility', 'toptal-ss' ), null, self::PAGE );
		add_settings_section( 'toptal_ss_locations_section', __( 'Locations', 'toptal-ss' ), null, self::PAGE );
		add_settings_section( 'toptal_ss_size_section', __( 'Button Size', 'toptal-ss' ), null, self::PAGE );
		add_settings_section( 'toptal_ss_appearance_section', __( 'Button Appearance', 'toptal-ss' ), null, self::PAGE );
		add_settings_section( 'toptal_ss_color_section', __( 'Button Color', 'toptal-ss' ), null, self::PAGE );

		foreach ( TopTal_SS_Networks::all() as $key => $network ) {
			$title = ! empty( $network['mobile_only'] )
				/* translators: %s: network name */
				? sprintf( __( 'Display %s share button? (mobile only)', 'toptal-ss' ), $network['label'] )
				/* translators: %s: network name */
				: sprintf( __( 'Display %s share button?', 'toptal-ss' ), $network['label'] );
			add_settings_field(
				'toptal_ss_network_' . $key,
				$title,
				array( $this, 'render_checkbox' ),
				self::PAGE,
				'toptal_ss_general_section',
				array(
					'section' => 'networks',
					'key'     => $key,
				)
			);
		}

		$visibility = array(
			'post' => __( 'Display on posts?', 'toptal-ss' ),
			'page' => __( 'Display on pages?', 'toptal-ss' ),
			'cpt'  => __( 'Display on custom post types?', 'toptal-ss' ),
		);
		foreach ( $visibility as $key => $title ) {
			add_settings_field(
				'toptal_ss_visibility_' . $key,
				$title,
				array( $this, 'render_checkbox' ),
				self::PAGE,
				'toptal_ss_visibility_section',
				array(
					'section' => 'post_types',
					'key'     => $key,
				)
			);
		}

		$locations = array(
			'below_post_title'      => __( 'Below the post title?', 'toptal-ss' ),
			'left_area'             => __( 'Float on left area?', 'toptal-ss' ),
			'after_post_content'    => __( 'After the post content?', 'toptal-ss' ),
			'inside_featured_image' => __( 'Inside the featured image?', 'toptal-ss' ),
		);
		foreach ( $locations as $key => $title ) {
			add_settings_field(
				'toptal_ss_location_' . $key,
				$title,
				array( $this, 'render_checkbox' ),
				self::PAGE,
				'toptal_ss_locations_section',
				array(
					'section' => 'locations',
					'key'     => $key,
				)
			);
		}

		add_settings_field( 'toptal_ss_size', __( 'Size:', 'toptal-ss' ), array( $this, 'render_size_select' ), self::PAGE, 'toptal_ss_size_section' );
		add_settings_field( 'toptal_ss_appearance', __( 'Style:', 'toptal-ss' ), array( $this, 'render_appearance_radio' ), self::PAGE, 'toptal_ss_appearance_section' );

		add_settings_field(
			'toptal_ss_default_colors',
			__( 'Default color?', 'toptal-ss' ),
			array( $this, 'render_checkbox' ),
			self::PAGE,
			'toptal_ss_color_section',
			array(
				'section' => null,
				'key'     => 'default_colors',
			)
		);

		foreach ( TopTal_SS_Networks::all() as $key => $network ) {
			add_settings_field(
				'toptal_ss_colors_' . $key,
				/* translators: %s: network name */
				sprintf( __( '%s Colors:', 'toptal-ss' ), $network['label'] ),
				array( $this, 'render_color_pickers' ),
				self::PAGE,
				'toptal_ss_color_section',
				array( 'key' => $key )
			);
		}
	}

	public function render_checkbox( array $args ): void {
		$settings = TopTal_SS_Options::get();
		$key      = $args['key'];
		$section  = $args['section'];

		if ( null === $section ) {
			$value = intval( $settings[ $key ] ?? 0 );
			$name  = TopTal_SS_Options::OPTION_KEY . '[' . $key . ']';
		} else {
			$value = intval( $settings[ $section ][ $key ] ?? 0 );
			$name  = TopTal_SS_Options::OPTION_KEY . '[' . $section . '][' . $key . ']';
		}

		printf(
			'<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
			esc_attr( $name ),
			checked( 1, $value, false ),
			esc_html__( 'Check for Yes', 'toptal-ss' )
		);
	}

	public function render_size_select(): void {
		$settings = TopTal_SS_Options::get();
		$sizes    = array(
			'small'  => __( 'Small', 'toptal-ss' ),
			'medium' => __( 'Medium', 'toptal-ss' ),
			'large'  => __( 'Large', 'toptal-ss' ),
		);

		echo '<select name="' . esc_attr( TopTal_SS_Options::OPTION_KEY . '[size]' ) . '">';
		foreach ( $sizes as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $settings['size'], $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	public function render_appearance_radio(): void {
		$settings = TopTal_SS_Options::get();
		$styles   = array(
			1 => __( 'Just icon', 'toptal-ss' ),
			2 => __( 'Just text', 'toptal-ss' ),
			3 => __( 'Icon + text', 'toptal-ss' ),
		);

		echo '<fieldset>';
		foreach ( $styles as $value => $label ) {
			printf(
				'<label><input type="radio" name="%s" value="%d" %s /> <span>%s</span></label><br>',
				esc_attr( TopTal_SS_Options::OPTION_KEY . '[appearance]' ),
				(int) $value,
				checked( intval( $settings['appearance'] ), $value, false ),
				esc_html( $label )
			);
		}
		echo '</fieldset>';
	}

	public function render_color_pickers( array $args ): void {
		$settings = TopTal_SS_Options::get();
		$key      = $args['key'];
		$colors   = $settings['colors'][ $key ];

		printf(
			'<label>%s <input type="text" name="%s" value="%s" class="color-field"></label> ',
			esc_html__( 'Background:', 'toptal-ss' ),
			esc_attr( TopTal_SS_Options::OPTION_KEY . '[colors][' . $key . '][bg]' ),
			esc_attr( $colors['bg'] )
		);
		printf(
			'<label>%s <input type="text" name="%s" value="%s" class="color-field"></label>',
			esc_html__( 'Font:', 'toptal-ss' ),
			esc_attr( TopTal_SS_Options::OPTION_KEY . '[colors][' . $key . '][text]' ),
			esc_attr( $colors['text'] )
		);
	}

	/**
	 * Sanitize the whole settings array. Unknown keys are dropped, every
	 * value is coerced against the defaults schema.
	 *
	 * @param mixed $input Raw POSTed value.
	 */
	public function sanitize_settings( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$clean = TopTal_SS_Options::defaults();

		foreach ( array( 'networks', 'post_types', 'locations' ) as $section ) {
			foreach ( $clean[ $section ] as $key => $_default ) {
				$clean[ $section ][ $key ] = ( intval( $input[ $section ][ $key ] ?? 0 ) === 1 ) ? 1 : 0;
			}
		}

		$size          = strtolower( (string) ( $input['size'] ?? '' ) );
		$clean['size'] = in_array( $size, array( 'small', 'medium', 'large' ), true ) ? $size : 'small';

		$appearance          = intval( $input['appearance'] ?? 0 );
		$clean['appearance'] = ( $appearance >= 1 && $appearance <= 3 ) ? $appearance : 3;

		$clean['default_colors'] = ( intval( $input['default_colors'] ?? 0 ) === 1 ) ? 1 : 0;

		foreach ( $clean['colors'] as $key => $_pair ) {
			foreach ( array( 'bg', 'text' ) as $field ) {
				$color                             = sanitize_hex_color( (string) ( $input['colors'][ $key ][ $field ] ?? '' ) );
				$clean['colors'][ $key ][ $field ] = is_string( $color ) ? $color : '';
			}
		}

		TopTal_SS_Options::flush_cache();

		return $clean;
	}
}
