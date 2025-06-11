<?php
/*
 * Plugin Name: TopTal Social Share
 * Description: Add various social networking share buttons to your website, including; Facebook, Twitter, Pinterest, LinkedIn and WhatsApp(mobile).
 * Author: Botez Costin
 * Version: 1.0
 * Author URI: https://ro.linkedin.com/in/costibotez
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

define( 'TOPTAL_SS_PLUGIN_DIR_URL', plugin_dir_url(__FILE__) );
define( 'TOPTAL_SS_PLUGIN_PATH', plugin_basename(__FILE__));
define( 'TOPTAL_SS_PLUGIN_DIR_ASSETS_URL', plugin_dir_url(__FILE__) . 'assets/');

class TopTal_Social_Share {

	function __construct() {
    add_action('wp_enqueue_scripts', 	   array($this, 'toptal_social_share_style'));
    add_action('admin_enqueue_scripts',  array($this, 'toptal_add_color_picker' ));
	  add_action('admin_menu', 			       array($this, 'toptal_social_share_menu_item'));
	  add_filter('body_class', 			       array($this, 'toptal_add_body_class'));
	  add_action('admin_init', 			       array($this, 'toptal_ss_settings'));
	  add_filter('the_content',            array($this, 'toptal_add_social_share_icons'));
    add_filter('the_title',              array($this, 'toptal_add_social_share_icons_title'), 99, 1);
    add_filter('post_thumbnail_html',    array($this, 'toptal_add_social_share_icons_image'), 99, 5);
    add_filter('plugin_action_links_' . TOPTAL_SS_PLUGIN_PATH, array($this, 'add_action_links' ));
    add_shortcode('toptal_ss',           array($this, 'toptal_ss_cb'));
    add_action('wp_footer',              array($this, 'toptal_float_area'), 10);
    register_uninstall_hook(__FILE__,    array('TopTal_Social_Share', 'toptal_plugin_uninstall'));
	}

  function toptal_add_color_picker( $hook ) {
    // enqueue color picker just on
    if($hook == 'settings_page_toptal_social_share') {
      // Add the color picker css file
      wp_enqueue_style( 'wp-color-picker' );
      // Include our custom jQuery file with WordPress Color Picker dependency
      wp_enqueue_script( 'toptal-script-handle', TOPTAL_SS_PLUGIN_DIR_ASSETS_URL . 'js/scripts.js', array( 'wp-color-picker' ), false, true );
    }
  }

	function toptal_social_share_style($hook) {
    	wp_register_style('toptal-ss-style', TOPTAL_SS_PLUGIN_DIR_ASSETS_URL . 'css/style.css');
    	wp_enqueue_style('toptal-ss-style');

      wp_register_style('toptal-ss-fontawesome', TOPTAL_SS_PLUGIN_DIR_ASSETS_URL . 'css/font-awesome.min.css');
      wp_enqueue_style('toptal-ss-fontawesome');
	}

	function toptal_social_share_menu_item() {
  	add_options_page('TopTal Social Share', 'TopTal Social Share', 'manage_options', 'toptal_social_share', array($this, 'toptal_social_share_page'));
	}

	function toptal_social_share_page() { ?>
		<div class="wrap">
      <h1><?php _e('TopTal Social Sharing Options', 'toptal-ss'); ?></h1>
      <form method="post" action="options.php">
        <?php
          settings_fields('toptal_ss_settings_all');
          do_settings_sections('toptal_social_share');
          submit_button();
        ?>
      </form>
    </div>
		<?php
	}

	function toptal_add_body_class($body_class) {
    if(get_option('toptal_ss_left_area') == 1)
      $body_class[] = 'toptal-ss-left-area';

    return $body_class;
	}

	function toptal_ss_settings() {
  	add_settings_section('toptal_ss_general_section', __('General Options', 'toptal-ss'), null, 'toptal_social_share');
    add_settings_section('toptal_ss_visibility_section', __('Visibility', 'toptal-ss'), null, 'toptal_social_share');
    add_settings_section('toptal_ss_locations_section', __('Locations', 'toptal-ss'), null, 'toptal_social_share');
    // add_settings_section('toptal_ss_order_section', __('Order', 'toptal-ss'), null, 'toptal_social_share');
    add_settings_section('toptal_ss_size_section', __('Button Size', 'toptal-ss'), null, 'toptal_social_share');
    add_settings_section('toptal_ss_appearance_section', __('Button Appearance', 'toptal-ss'), null, 'toptal_social_share');
    add_settings_section('toptal_ss_color_section', __('Button Color', 'toptal-ss'), null, 'toptal_social_share');

    // GENERAL SETTINGS
  	add_settings_field('toptal_ss_facebook', 	__('Display Facebook share button?', 'toptal-ss'), 	array($this, 'toptal_ss_facebook_checkbox'), 'toptal_social_share', 'toptal_ss_general_section');
  	add_settings_field('toptal_ss_twitter',  	__('Display Twitter share button?', 'toptal-ss'), 	array($this, 'toptal_ss_twitter_checkbox'), 'toptal_social_share', 'toptal_ss_general_section');
  	add_settings_field('toptal_ss_linkedin', 	__('Display LinkedIn share button?', 'toptal-ss'), 	array($this, 'toptal_ss_linkedin_checkbox'), 'toptal_social_share', 'toptal_ss_general_section');
  	add_settings_field('toptal_ss_pinterest', __('Display Pinterest share button?', 'toptal-ss'), array($this, 'toptal_ss_pinterest_checkbox'), 'toptal_social_share', 'toptal_ss_general_section');
    add_settings_field('toptal_ss_whatsapp',  __('Display WhatsApp share button? (mobile only)', 'toptal-ss'),   array($this, 'toptal_ss_whatsapp_checkbox'), 'toptal_social_share', 'toptal_ss_general_section');

    // VISIBILITY SETTINGS
    add_settings_field('toptal_ss_posts', __('Display on posts?', 'toptal-ss'), array($this, 'toptal_ss_posts_checkbox'), 'toptal_social_share', 'toptal_ss_visibility_section');
    add_settings_field('toptal_ss_page', __('Display on pages?', 'toptal-ss'), array($this, 'toptal_ss_page_checkbox'), 'toptal_social_share', 'toptal_ss_visibility_section');
    add_settings_field('toptal_ss_cpt', __('Display on custom post types?', 'toptal-ss'), array($this, 'toptal_ss_cpt_checkbox'), 'toptal_social_share', 'toptal_ss_visibility_section');

    // LOCATIONS SETTINGS
    add_settings_field('toptal_ss_below_post_title', __('Below the post title?', 'toptal-ss'), array($this, 'toptal_ss_below_post_title_checkbox'), 'toptal_social_share', 'toptal_ss_locations_section');
    add_settings_field('toptal_ss_left_area', __('Float on left area?', 'toptal-ss'), array($this, 'toptal_ss_left_area_checkbox'), 'toptal_social_share', 'toptal_ss_locations_section');
    add_settings_field('toptal_ss_after_post_content', __('After the post content?', 'toptal-ss'), array($this, 'toptal_ss_after_post_content_checkbox'), 'toptal_social_share', 'toptal_ss_locations_section');
    add_settings_field('toptal_ss_inside_featured_image', __('Inside the featured image?', 'toptal-ss'), array($this, 'toptal_ss_inside_featured_image_checkbox'), 'toptal_social_share', 'toptal_ss_locations_section');

    // // ORDER
    // if(get_option('toptal_ss_facebook') == 1) {
    //   add_settings_field('toptal_ss_facebook_order',  __('Facebook', 'toptal-ss'),  array($this, 'toptal_ss_facebook_order'), 'toptal_social_share', 'toptal_ss_order_section');
    //   register_setting('toptal_ss_settings_all', 'toptal_ss_facebook_order', 'intval');
    // }
    // if(get_option('toptal_ss_twitter') == 1) {
    //   add_settings_field('toptal_ss_twitter_order',   __('Twitter', 'toptal-ss'),   array($this, 'toptal_ss_twitter_order'), 'toptal_social_share', 'toptal_ss_order_section');
    //   register_setting('toptal_ss_settings_all', 'toptal_ss_twitter_order', 'intval');
    // }
    // if(get_option('toptal_ss_linkedin') == 1) {
    //   add_settings_field('toptal_ss_linkedin_order',  __('LinkedIn', 'toptal-ss'),  array($this, 'toptal_ss_linkedin_order'), 'toptal_social_share', 'toptal_ss_order_section');
    //   register_setting('toptal_ss_settings_all', 'toptal_ss_linkedin_order', 'intval');
    // }
    // if(get_option('toptal_ss_pinterest') == 1) {
    //   add_settings_field('toptal_ss_pinterest_order', __('Pinterest', 'toptal-ss'), array($this, 'toptal_ss_pinterest_order'), 'toptal_social_share', 'toptal_ss_order_section');
    //   register_setting('toptal_ss_settings_all', 'toptal_ss_pinterest_order', 'intval');
    // }
    // }
    // if(get_option('toptal_ss_whatsapp') == 1) {
    //   add_settings_field('toptal_ss_whatsapp_order',  __('WhatsApp (mobile only)', 'toptal-ss'),   array($this, 'toptal_ss_whatsapp_order'), 'toptal_social_share', 'toptal_ss_order_section');
    //   register_setting('toptal_ss_settings_all', 'toptal_ss_whatsapp_order', 'intval');
    // }

    // SIZE SETTINGS
    add_settings_field('toptal_ss_size', __('Small?', 'toptal-ss'), array($this, 'toptal_ss_select'), 'toptal_social_share', 'toptal_ss_size_section');

    // APPEARANCE SETTINGS
    add_settings_field('toptal_ss_appearance', __('Style:', 'toptal-ss'), array($this, 'toptal_ss_appearance_radio'), 'toptal_social_share', 'toptal_ss_appearance_section');

    // DEFAULT COLOR SETTING
    add_settings_field('toptal_ss_color', __('Default color?', 'toptal-ss'), array($this, 'toptal_ss_color_checkbox'), 'toptal_social_share', 'toptal_ss_color_section');

    // CUSTOM COLORS SETTINGS
    if(get_option('toptal_ss_color') == 0) {
      if(get_option('toptal_ss_facebook') == 1) {
        add_settings_field('toptal_ss_facebook_bk_color',  __('Facebook Background Color:', 'toptal-ss'),  array($this, 'toptal_ss_facebook_bk_colorpicker'), 'toptal_social_share', 'toptal_ss_color_section');
        add_settings_field('toptal_ss_facebook_color',  __('Facebook Font Color:', 'toptal-ss'),  array($this, 'toptal_ss_facebook_colorpicker'), 'toptal_social_share', 'toptal_ss_color_section');
        register_setting('toptal_ss_settings_all', 'toptal_ss_facebook_bk_color', 'sanitize_hex_color');
        register_setting('toptal_ss_settings_all', 'toptal_ss_facebook_color', 'sanitize_hex_color');
      }
      if(get_option('toptal_ss_twitter') == 1) {
        add_settings_field('toptal_ss_twitter_bk_color',   __('Twitter Background Color:', 'toptal-ss'),   array($this, 'toptal_ss_twitter_bk_colorpicker'), 'toptal_social_share', 'toptal_ss_color_section');
        add_settings_field('toptal_ss_twitter_color',   __('Twitter Font Color:', 'toptal-ss'),   array($this, 'toptal_ss_twitter_colorpicker'), 'toptal_social_share', 'toptal_ss_color_section');
        register_setting('toptal_ss_settings_all', 'toptal_ss_twitter_bk_color', 'sanitize_hex_color');
        register_setting('toptal_ss_settings_all', 'toptal_ss_twitter_color', 'sanitize_hex_color');
      }
      if(get_option('toptal_ss_linkedin') == 1) {
        add_settings_field('toptal_ss_linkedin_bk_color',  __('LinkedIn Background Color:', 'toptal-ss'),  array($this, 'toptal_ss_linkedin_bk_colorpicker'), 'toptal_social_share', 'toptal_ss_color_section');
        add_settings_field('toptal_ss_linkedin_color',  __('LinkedIn Font Color:', 'toptal-ss'),  array($this, 'toptal_ss_linkedin_colorpicker'), 'toptal_social_share', 'toptal_ss_color_section');
        register_setting('toptal_ss_settings_all', 'toptal_ss_linkedin_bk_color', 'sanitize_hex_color');
        register_setting('toptal_ss_settings_all', 'toptal_ss_linkedin_color', 'sanitize_hex_color');
      }
      if(get_option('toptal_ss_pinterest') == 1) {
        add_settings_field('toptal_ss_pinterest_bk_color', __('Pinterest Background Color:', 'toptal-ss'), array($this, 'toptal_ss_pinterest_bk_colorpicker'), 'toptal_social_share', 'toptal_ss_color_section');
        add_settings_field('toptal_ss_pinterest_color', __('Pinterest Font Color:', 'toptal-ss'), array($this, 'toptal_ss_pinterest_colorpicker'), 'toptal_social_share', 'toptal_ss_color_section');
        register_setting('toptal_ss_settings_all', 'toptal_ss_pinterest_bk_color', 'sanitize_hex_color');
        register_setting('toptal_ss_settings_all', 'toptal_ss_pinterest_color', 'sanitize_hex_color');
      }
      }
      if(get_option('toptal_ss_whatsapp') == 1) {
        add_settings_field('toptal_ss_whatsapp_bk_color',  __('WhatsApp Background Color (mobile only):', 'toptal-ss'),   array($this, 'toptal_ss_whatsapp_bk_colorpicker'), 'toptal_social_share', 'toptal_ss_color_section');
        add_settings_field('toptal_ss_whatsapp_color',  __('WhatsApp Font Color (mobile only):', 'toptal-ss'),   array($this, 'toptal_ss_whatsapp_colorpicker'), 'toptal_social_share', 'toptal_ss_color_section');
        register_setting('toptal_ss_settings_all', 'toptal_ss_whatsapp_bk_color', 'sanitize_hex_color');
        register_setting('toptal_ss_settings_all', 'toptal_ss_whatsapp_color', 'sanitize_hex_color');
      }
    }

    // GENERAL SETTINGS
  	register_setting('toptal_ss_settings_all', 'toptal_ss_facebook', 'intval');
  	register_setting('toptal_ss_settings_all', 'toptal_ss_twitter', 'intval');
  	register_setting('toptal_ss_settings_all', 'toptal_ss_linkedin', 'intval');
  	register_setting('toptal_ss_settings_all', 'toptal_ss_pinterest', 'intval');
    register_setting('toptal_ss_settings_all', 'toptal_ss_whatsapp', 'intval');

    // VISIBILITY SETTINGS
    register_setting('toptal_ss_settings_all', 'toptal_ss_posts', 'intval');
    register_setting('toptal_ss_settings_all', 'toptal_ss_page', 'intval');
    register_setting('toptal_ss_settings_all', 'toptal_ss_cpt', 'intval');

    // LOCATIONS SETTINGS
    register_setting('toptal_ss_settings_all', 'toptal_ss_below_post_title', 'intval');
    register_setting('toptal_ss_settings_all', 'toptal_ss_left_area', 'intval');
    register_setting('toptal_ss_settings_all', 'toptal_ss_after_post_content', 'intval');
    register_setting('toptal_ss_settings_all', 'toptal_ss_inside_featured_image', 'intval');

    // SIZE SETTINGS
    register_setting('toptal_ss_settings_all', 'toptal_ss_size', 'strval');

    // APPEARANCE SETTINGS
    register_setting('toptal_ss_settings_all', 'toptal_ss_appearance', 'intval');

    // COLOR SETTING
    register_setting('toptal_ss_settings_all', 'toptal_ss_color', 'intval');
	}

  // GENERAL SETTINGS CALLBACK
	function toptal_ss_facebook_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_facebook" value="1" <?php checked(1, get_option('toptal_ss_facebook'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
 		<?php
	}

	function toptal_ss_twitter_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_twitter" value="1" <?php checked(1, get_option('toptal_ss_twitter'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
   	<?php
	}

	function toptal_ss_linkedin_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_linkedin" value="1" <?php checked(1, get_option('toptal_ss_linkedin'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
		<?php
	}

	function toptal_ss_pinterest_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_pinterest" value="1" <?php checked(1, get_option('toptal_ss_pinterest'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
 		<?php
	}

 		<?php
	}

  function toptal_ss_whatsapp_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_whatsapp" value="1" <?php checked(1, get_option('toptal_ss_whatsapp'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
    <?php
  }

  // VISIBILITY SETTINGS CALLBACK
  function toptal_ss_posts_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_posts" value="1" <?php checked(1, get_option('toptal_ss_posts'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
    <?php
  }

  function toptal_ss_page_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_page" value="1" <?php checked(1, get_option('toptal_ss_page'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
    <?php
  }

  function toptal_ss_cpt_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_cpt" value="1" <?php checked(1, get_option('toptal_ss_cpt'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
    <?php
  }

  // LOCATIONS SETTINGS CALLBACK
  function toptal_ss_below_post_title_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_below_post_title" value="1" <?php checked(1, get_option('toptal_ss_below_post_title'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
    <?php
  }

  function toptal_ss_left_area_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_left_area" value="1" <?php checked(1, get_option('toptal_ss_left_area'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
    <?php
  }

  function toptal_ss_after_post_content_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_after_post_content" value="1" <?php checked(1, get_option('toptal_ss_after_post_content'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
    <?php
  }

  function toptal_ss_inside_featured_image_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_inside_featured_image" value="1" <?php checked(1, get_option('toptal_ss_inside_featured_image'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
    <?php
  }

  // APPEARANCE SETTINGS CALLBACK
  function toptal_ss_appearance_radio() { ?>
    <fieldset>
      <label>
        <input type="radio" name="toptal_ss_appearance" value="1"<?php checked( '1' == get_option('toptal_ss_appearance') ); ?> />
        <span class=""><?php _e('Just icon', 'toptal-ss'); ?></span>
      </label>
      <br>
      <label>
        <input type="radio" name="toptal_ss_appearance" value="2"<?php checked( '2' == get_option('toptal_ss_appearance') ); ?> />
        <span class=""><?php _e('Just text', 'toptal-ss'); ?></span>
      </label>
      <br>
      <label>
        <input type="radio" name="toptal_ss_appearance" value="3"<?php checked( '3' == get_option('toptal_ss_appearance') ); ?> />
        <span class=""><?php _e('Icon + text', 'toptal-ss'); ?></span>
      </label>
    </fieldset>
    <?php
  }

  // CUSTOM COLOR SETTINGS CALLBACK
  function toptal_ss_facebook_colorpicker() { ?>
    <input type="text" name="toptal_ss_facebook_color" value="<?php echo esc_attr( get_option('toptal_ss_facebook_color') ); ?>" class="color-field">
    <?php
  }

  function toptal_ss_twitter_colorpicker() { ?>
    <input type="text" name="toptal_ss_twitter_color" value="<?php echo esc_attr( get_option('toptal_ss_twitter_color') ); ?>" class="color-field">
    <?php
  }

  function toptal_ss_linkedin_colorpicker() { ?>
    <input type="text" name="toptal_ss_linkedin_color" value="<?php echo esc_attr( get_option('toptal_ss_linkedin_color') ); ?>" class="color-field">
    <?php
  }

  function toptal_ss_pinterest_colorpicker() { ?>
    <input type="text" name="toptal_ss_pinterest_color" value="<?php echo esc_attr( get_option('toptal_ss_pinterest_color') ); ?>" class="color-field">
    <?php
  }

    <?php
  }

  function toptal_ss_whatsapp_colorpicker() { ?>
    <input type="text" name="toptal_ss_whatsapp_color" value="<?php echo esc_attr( get_option('toptal_ss_whatsapp_color') ); ?>" class="color-field">
    <?php
  }

  function toptal_ss_facebook_bk_colorpicker() { ?>
    <input type="text" name="toptal_ss_facebook_bk_color" value="<?php echo esc_attr( get_option('toptal_ss_facebook_bk_color') ); ?>" class="color-field">
    <?php
  }

  function toptal_ss_twitter_bk_colorpicker() { ?>
    <input type="text" name="toptal_ss_twitter_bk_color" value="<?php echo esc_attr( get_option('toptal_ss_twitter_bk_color') ); ?>" class="color-field">
    <?php
  }

  function toptal_ss_linkedin_bk_colorpicker() { ?>
    <input type="text" name="toptal_ss_linkedin_bk_color" value="<?php echo esc_attr( get_option('toptal_ss_linkedin_bk_color') ); ?>" class="color-field">
    <?php
  }

  function toptal_ss_pinterest_bk_colorpicker() { ?>
    <input type="text" name="toptal_ss_pinterest_bk_color" value="<?php echo esc_attr( get_option('toptal_ss_pinterest_bk_color') ); ?>" class="color-field">
    <?php
  }

    <?php
  }

  function toptal_ss_whatsapp_bk_colorpicker() { ?>
    <input type="text" name="toptal_ss_whatsapp_bk_color" value="<?php echo esc_attr( get_option('toptal_ss_whatsapp_bk_color') ); ?>" class="color-field">
    <?php
  }

  // SIZE SETTINGS CALLBACK
  function toptal_ss_select() { ?>
    <select name="toptal_ss_size">
      <option name="small" <?php selected( get_option('toptal_ss_size'), 'Small' ); ?>><?php _e('Small', 'toptal-ss'); ?></option>
      <option name="medium" <?php selected( get_option('toptal_ss_size'), 'Medium' ); ?>><?php _e('Medium', 'toptal-ss'); ?></option>
      <option name="large" <?php selected( get_option('toptal_ss_size'), 'Large' ); ?>><?php _e('Large', 'toptal-ss'); ?></option>
    </select>
    <?php
  }

  function toptal_ss_color_checkbox() { ?>
    <input type="checkbox" name="toptal_ss_color" value="1" <?php checked(1, get_option('toptal_ss_color'), true); ?> /> <?php _e('Check for Yes', 'toptal-ss'); ?>
    <?php
  }

  /**
   * Add social share bottons to the end of every post/page.
   *
   * @uses pre_validate()
   * @uses toptal_social_html()
   * @param $content String
   */
	function toptal_add_social_share_icons($content) {
  	global $post;
    // echo '</pre>'; print_r(get_post_types()); exit;
    $html = '';

    if($this->pre_validate($content, $post->ID) !== TRUE) return $content;

    if(get_option('toptal_ss_after_post_content') == 1) {
      $html =  $this->toptal_social_html($post->ID);
    }
  	return $content .= $html;
	}

  /**
   * Add social share bottons after title
   *
   * @uses pre_validate()
   * @uses toptal_social_html()
   * @param $title String
   */
  function toptal_add_social_share_icons_title($title) {
    global $post;
    if(in_the_loop()) {
      $html = '';

      if($this->pre_validate($title, $post->ID) !== TRUE) return $title;

      if(get_option('toptal_ss_below_post_title') == 1) {
        $html =  $this->toptal_social_html($post->ID);
      }
      return $title .= $html;
    }
    return $title;
  }

  /**
   * Add social share bottons after featured iamge
   *
   * @uses pre_validate()
   * @uses toptal_social_html()
   * @param $html_image HTML
   */
  function toptal_add_social_share_icons_image($html_image, $post_id, $post_thumbnail_id, $size, $attr ) {
    global $post;

    if(in_the_loop()) {
      $html = '';

      if($this->pre_validate($html_image, $post->ID) !== TRUE) return $html_image;

      if(get_option('toptal_ss_inside_featured_image') == 1) {
        $html = $this->toptal_social_html($post->ID);
      }
      $html_image .= $html;
      return $html_image;
    }
    return $html_image;
  }

  function pre_validate($content, $post_id) {
    $not_cpt = array('post', 'page', 'attachment', 'revision', 'nav_menu_item');

    // VALIDATE POSTS
    if(get_post_type($post_id) == 'post' && get_option('toptal_ss_posts') == 0) return $content;

    // VALIDATE PAGES
    if(get_post_type($post_id) == 'page' && get_option('toptal_ss_page') == 0) return $content;

    // VALIDATE CPTs
    if(!in_array(get_post_type($post_id), $not_cpt) && get_option('toptal_ss_cpt') == 0) return $content;

    return TRUE;
  }

  function toptal_social_html($post_id, $atts = array()) {
    $just_icon = (intval(get_option('toptal_ss_appearance')) == 1) ? 'just_icon' : '';
    $html = '<div class="toptal-social-share-wrapper ' . $just_icon . '">';
    $url = esc_url(get_permalink($post_id));

    $size = 'small';

    $facebook_style  = "";
    $twitter_style   = "";
    $linkedin_style  = "";
    $pinterest_style = "";
    $whatsapp_style  = "";

    if(get_option('toptal_ss_color') == 0) {
      $facebook_style  = 'style="background-color:' . esc_attr( get_option('toptal_ss_facebook_bk_color') ) . '; color:' . esc_attr( get_option('toptal_ss_facebook_color') ) . '"';
      $twitter_style   = 'style="background-color:' . esc_attr( get_option('toptal_ss_twitter_bk_color') ) . '; color:' . esc_attr( get_option('toptal_ss_twitter_color') ) . '"';
      $linkedin_style  = 'style="background-color:' . esc_attr( get_option('toptal_ss_linkedin_bk_color') ) . '; color:' . esc_attr( get_option('toptal_ss_linkedin_color') ) . '"';
      $pinterest_style = 'style="background-color:' . esc_attr( get_option('toptal_ss_pinterest_bk_color') ) . '; color:' . esc_attr( get_option('toptal_ss_pinterest_color') ) . '"';
      $whatsapp_style  = 'style="background-color:' . esc_attr( get_option('toptal_ss_whatsapp_bk_color') ) . '; color:' . esc_attr( get_option('toptal_ss_whatsapp_color') ) . '"';
    }

    $style = intval(get_option('toptal_ss_appearance'));
    switch ($style) {
      case 1:
        $facebook_text  = '<a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=' . $url . '"><span class="fa fa-facebook icon"></span></a>';
        $twitter_text   = '<a target="_blank" href="https://twitter.com/home?status=' . $url . '"><span class="fa fa-twitter icon"></span></a>';
        $linkedin_text  = '<a target="_blank" href="https://www.linkedin.com/shareArticle?mini=true&url=' . $url . '"><span class="fa fa-linkedin icon"></span></a>';
        $pinterest_text = '<a target="_blank" href="https://pinterest.com/pin/create/button/?url=' . $url . '"><span class="fa fa-pinterest icon"></span></a>';
        break;
      case 2:
        $facebook_text  = '<a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=' . $url . '">Facebook</a>';
        $twitter_text   = '<a target="_blank" href="https://twitter.com/home?status=' . $url . '">Twitter</a>';
        $linkedin_text  = '<a target="_blank" href="https://www.linkedin.com/shareArticle?mini=true&url=' . $url . '">LinkedIn</a>';
        $pinterest_text = '<a target="_blank" href="https://pinterest.com/pin/create/button/?url=' . $url . '">Pinterest</a>';
        $whatsapp_text  = '<a target="_blank" href="https://api.whatsapp.com/send?text=' . $url . '">WhatsApp</a>';
        break;
      case 3:
        $facebook_text  = '<a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=' . $url . '"><span class="fa fa-facebook"></span>Facebook</a>';
        $twitter_text   = '<a target="_blank" href="https://twitter.com/home?status=' . $url . '"><span class="fa fa-twitter"></span>Twitter</a>';
        $linkedin_text  = '<a target="_blank" href="https://www.linkedin.com/shareArticle?mini=true&url=' . $url . '"><span class="fa fa-linkedin"></span>LinkedIn</a>';
        $pinterest_text = '<a target="_blank" href="https://pinterest.com/pin/create/button/?url=' . $url . '"><span class="fa fa-pinterest"></span>Pinterest</a>';
        $whatsapp_text  = '<a target="_blank" href="https://api.whatsapp.com/send?text=' . $url . '"><span class="fa fa-whatsapp"></span>WhatsApp</a>';
        break;
    }

    if(count($atts)==0) {
      switch (get_option('toptal_ss_size')) {
        case 'Small':
          $size = 'small';
          break;
        case 'Medium':
          $size = 'medium';
          break;
        case 'Large':
          $size = 'large';
          break;
      }
      if(get_option('toptal_ss_facebook') == 1)
        $html .= '<div class="facebook ' . $size . '" ' . $facebook_style . '>' . $facebook_text . '</div>';

      if(get_option('toptal_ss_twitter') == 1)
        $html .= '<div class="twitter ' . $size . '" ' . $twitter_style . '>' . $twitter_text . '</div>';

      if(get_option('toptal_ss_linkedin') == 1)
        $html .= '<div class="linkedin ' . $size . '" ' . $linkedin_style .  '>' . $linkedin_text . '</div>';

      if(get_option('toptal_ss_pinterest') == 1)
        $html .= '<div class="pinterest ' . $size . '" ' . $pinterest_style . '>' . $pinterest_text . '</div>';


      if(get_option('toptal_ss_whatsapp') == 1 && $this->toptal_ss_is_mobile())
        $html .= '<div class="whatsapp ' . $size . '"' . $whatsapp_style .  '>' . $whatsapp_text . '</div>';

      $html .= '<div class="clear"></div></div><div class="clear"></div>';

    } else {
      switch ($atts['size']) {
        case 'small':
          $size = 'small';
          break;
        case 'medium':
          $size = 'medium';
          break;
        case 'large':
          $size = 'large';
          break;
      }

      if($atts['facebook'] == 1)
        $html .= '<div class="facebook ' . $size . '"' . $facebook_style . '>' . $facebook_text . '</div>';

      if($atts['twitter'] == 1)
        $html .= '<div class="twitter ' . $size . '"' . $twitter_style . '>' . $twitter_text . '</div>';

      if($atts['linkedin'] == 1)
        $html .= '<div class="linkedin ' . $size . '"' . $linkedin_style .  '>' . $linkedin_text . '</div>';

      if($atts['pinterest'] == 1)
        $html .= '<div class="pinterest ' . $size . '"' . $pinterest_style . '>' . $pinterest_text . '</div>';


      if($atts['whatsapp'] == 1 && $this->toptal_ss_is_mobile())
        $html .= '<div class="whatsapp ' . $size . '"' . $whatsapp_style .  '>' . $whatsapp_text . '</div>';

      $html .= '<div class="clear"></div></div><div class="clear"></div>';
    }
    return $html;
  }

  function toptal_ss_is_mobile() {
    // Check the server headers to see if they're mobile friendly
    if(isset($_SERVER["HTTP_X_WAP_PROFILE"]))
        return true;

    // If the http_accept header supports wap then it's a mobile too
    if(isset($_SERVER["HTTP_ACCEPT"]))
      if(preg_match("/wap\.|\.wap/i",$_SERVER["HTTP_ACCEPT"]))
        return true;

    // Still no luck? Let's have a look at the user agent on the browser. If it contains
    // any of the following, it's probably a mobile device. Kappow!
    if(isset($_SERVER["HTTP_USER_AGENT"])) {
        $user_agents = array("midp", "j2me", "avantg", "docomo", "novarra", "palmos", "palmsource", "240x320", "opwv", "chtml", "pda", "windows\ ce", "mmp\/", "blackberry", "mib\/", "symbian", "wireless", "nokia", "hand", "mobi", "phone", "cdm", "up\.b", "audio", "SIE\-", "SEC\-", "samsung", "HTC", "mot\-", "mitsu", "sagem", "sony", "alcatel", "lg", "erics", "vx", "NEC", "philips", "mmm", "xx", "panasonic", "sharp", "wap", "sch", "rover", "pocket", "benq", "java", "pt", "pg", "vox", "amoi", "bird", "compal", "kg", "voda", "sany", "kdd", "dbt", "sendo", "sgh", "gradi", "jb", "\d\d\di", "moto");
        foreach($user_agents as $user_string){
            if(preg_match("/".$user_string."/i", $_SERVER["HTTP_USER_AGENT"])) {
                return true;
            }
        }
    }
    // None of the above? Then it's probably not a mobile device.
    return false;
  }

  function toptal_ss_cb($atts) {
    global $post;

    $a = shortcode_atts( array(
        'size'          => 'small',
        'facebook'      => 0,
        'twitter'       => 0,
        'linkedin'      => 0,
        'pinterest'     => 0,
        'whatsapp'      => 0,
    ), $atts );

    return $this->toptal_social_html($post->ID, $a);
  }

  function add_action_links ( $links ) {
    $mylinks = array(
      '<a href="' . admin_url( 'options-general.php?page=toptal_social_share' ) . '">' . __('Settings', 'toptal-ss') .  '</a>',
    );
    return array_merge( $links, $mylinks );
  }

  function toptal_float_area() {
    if (intval(get_option('toptal_ss_left_area')) !== 1) {
      return;
    }

    $html = $this->toptal_social_html(get_the_ID());
    $html = str_replace(
      'toptal-social-share-wrapper',
      'toptal-social-share-wrapper float-area',
      $html
    );

    echo $html;
  }

  static function toptal_plugin_uninstall() {
    // GENERAL SETTINGS
    delete_option('toptal_ss_facebook');
    delete_option('toptal_ss_twitter');
    delete_option('toptal_ss_linkedin');
    delete_option('toptal_ss_pinterest');
    delete_option('toptal_ss_whatsapp');

    // VISIBILITY SETTINGS
    delete_option('toptal_ss_posts');
    delete_option('toptal_ss_page');
    delete_option('toptal_ss_cpt');

    // LOCATIONS SETTINGS
    delete_option('toptal_ss_below_post_title');
    delete_option('toptal_ss_left_area');
    delete_option('toptal_ss_after_post_content');
    delete_option('toptal_ss_inside_featured_image');

    // SIZE SETTINGS
    delete_option('toptal_ss_size');

    // APPEARANCE SETTINGS
    delete_option('toptal_ss_appearance');

    // COLOR SETTING
    delete_option('toptal_ss_color');
  }
}
new TopTal_Social_Share();


?>
