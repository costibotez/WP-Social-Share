<?php
// Minimal WP stubs to smoke-test the refactored plugin outside WordPress.
error_reporting(E_ALL);

$GLOBALS['__options'] = [];
$GLOBALS['__transients'] = [];
$GLOBALS['__post_meta'] = [];
$GLOBALS['__post_status'] = 'publish';
$GLOBALS['__post_type'] = 'post';
$GLOBALS['__hooks'] = [];

define('ABSPATH', '/');
define('MINUTE_IN_SECONDS', 60);
function plugin_dir_url($f) { return 'http://example.test/wp-content/plugins/wp-social-share/'; }
function plugin_basename($f) { return 'wp-social-share/toptal-social-share.php'; }
function register_activation_hook($f, $cb) {}
function add_action(...$a) {}
function add_filter(...$a) {}
function add_shortcode(...$a) {}
function apply_filters($tag, $value) { return $value; }
function get_option($k, $default = false) { return array_key_exists($k, $GLOBALS['__options']) ? $GLOBALS['__options'][$k] : $default; }
function add_option($k, $v) { if (!array_key_exists($k, $GLOBALS['__options'])) { $GLOBALS['__options'][$k] = $v; return true; } return false; }
function update_option($k, $v) { $GLOBALS['__options'][$k] = $v; return true; }
function delete_option($k) { unset($GLOBALS['__options'][$k]); return true; }
function get_permalink($id) { return 'http://example.test/?p=' . $id . '&lang=en'; }
function get_post_type($p = null) { return $GLOBALS['__post_type']; }
function get_post_status($id) { return $GLOBALS['__post_status']; }
function is_post_type_viewable($t) { return $t === 'post'; }
function get_post_meta($id, $k, $single) { return $GLOBALS['__post_meta'][$id][$k] ?? ''; }
function update_post_meta($id, $k, $v) { $GLOBALS['__post_meta'][$id][$k] = $v; }
function get_transient($k) { return $GLOBALS['__transients'][$k] ?? false; }
function set_transient($k, $v, $ttl) { $GLOBALS['__transients'][$k] = $v; }
function sanitize_key($k) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$k)); }
function sanitize_hex_color($color) { if ('' === $color) return ''; return preg_match('/^#([A-Fa-f0-9]{3}){1,2}$/', $color) ? $color : null; }
function esc_attr($v) { return htmlspecialchars((string)$v, ENT_QUOTES); }
function esc_html($v) { return htmlspecialchars((string)$v, ENT_QUOTES); }
function esc_url($v) { return str_replace('&', '&#038;', (string)$v); }
function in_the_loop() { return true; }
function is_singular() { return true; }
function shortcode_atts($defaults, $atts) { return array_merge($defaults, array_intersect_key((array)$atts, $defaults)); }
function get_the_ID() { return $GLOBALS['post'] instanceof WP_Post ? $GLOBALS['post']->ID : false; }
function get_post() { return $GLOBALS['post'] ?? null; }
function has_shortcode($content, $tag) { return strpos((string)$content, '[' . $tag) !== false; }
function __($s, $d = null) { return $s; }
function _e($s, $d = null) { echo $s; }
function wp_create_nonce($action) { return 'test-nonce'; }
function check_ajax_referer($action, $field) {
  if (!isset($_POST[$field]) || $_POST[$field] !== 'test-nonce') { throw new RuntimeException('NONCE_FAIL'); }
}
class JsonResponse extends Exception {
  public $ok; public $payload; public $status;
  public function __construct($ok, $payload, $status) { $this->ok = $ok; $this->payload = $payload; $this->status = $status; parent::__construct($ok ? 'success' : 'error'); }
}
function wp_send_json_success($data = null, $status = null) { throw new JsonResponse(true, $data, $status); }
function wp_send_json_error($data = null, $status = null) { throw new JsonResponse(false, $data, $status); }
function wp_register_style(...$a) {}
function wp_register_script(...$a) {}
function wp_localize_script(...$a) {}
function wp_enqueue_style(...$a) {}
function wp_enqueue_script(...$a) {}
function wp_style_is($handle, $status) { return true; }
function admin_url($p = '') { return 'http://example.test/wp-admin/' . $p; }
function load_plugin_textdomain(...$a) {}
function wp_kses_post($html) { return $html; }
class WP_Post { public $ID = 42; public $post_content = ''; }

require dirname(__DIR__) . '/toptal-social-share.php';

// ---- 1. Legacy migration -------------------------------------------------
$GLOBALS['__options'] = [
  'toptal_ss_facebook' => 1, 'toptal_ss_twitter' => '1', 'toptal_ss_linkedin' => 0,
  'toptal_ss_pinterest' => 1, 'toptal_ss_whatsapp' => 1,
  'toptal_ss_posts' => 1, 'toptal_ss_page' => 0, 'toptal_ss_cpt' => 1,
  'toptal_ss_below_post_title' => 1, 'toptal_ss_left_area' => 1,
  'toptal_ss_after_post_content' => 1, 'toptal_ss_inside_featured_image' => 0,
  'toptal_ss_size' => 'Medium', 'toptal_ss_appearance' => 2, 'toptal_ss_color' => 0,
  'toptal_ss_facebook_bk_color' => '#123456', 'toptal_ss_facebook_color' => '#ffffff',
];
TopTal_SS_Options::flush_cache();
$s = TopTal_SS_Options::get();
assert($s['networks']['facebook'] === 1 && $s['networks']['twitter'] === 1 && $s['networks']['linkedin'] === 0, 'networks migrated');
assert($s['post_types']['post'] === 1 && $s['post_types']['page'] === 0 && $s['post_types']['cpt'] === 1, 'post types migrated');
assert($s['size'] === 'medium' && $s['appearance'] === 2 && $s['default_colors'] === 0, 'size/appearance/colors flag migrated');
assert($s['colors']['facebook']['bg'] === '#123456' && $s['colors']['facebook']['text'] === '#ffffff', 'colors migrated');
assert(get_option('toptal_ss_facebook') === false, 'legacy options deleted');
assert(is_array(get_option('toptal_ss_settings')), 'new option persisted');

// ---- 2. Renderer ---------------------------------------------------------
$renderer = new TopTal_SS_Renderer();
$GLOBALS['post'] = new WP_Post();

$html = $renderer->buttons_html(42);
assert(strpos($html, 'twitter.com/intent/tweet?url=http%3A%2F%2Fexample.test%2F%3Fp%3D42%26lang%3Den') !== false, 'encoded twitter url');
assert(strpos($html, 'class="facebook medium"') !== false, 'facebook rendered with migrated size');
assert(strpos($html, 'class="linkedin') === false, 'disabled network not rendered');
assert(strpos($html, 'class="whatsapp medium"') !== false, 'whatsapp always rendered (CSS hides on desktop)');
assert(strpos($html, 'style="background-color:#123456; color:#ffffff"') !== false, 'custom colors applied');
assert(strpos($html, '>Facebook</a>') !== false && strpos($html, 'fa-facebook') === false, 'appearance=2 text only');

// Appearance 1 renders icons, no labels, just_icon class.
$stored = get_option('toptal_ss_settings');
$stored['appearance'] = 1;
update_option('toptal_ss_settings', $stored);
TopTal_SS_Options::flush_cache();
$html = $renderer->buttons_html(42);
assert(strpos($html, 'just_icon') !== false && strpos($html, 'fa-facebook icon') !== false && strpos($html, '>Facebook<') === false, 'appearance=1 icon only');

// ---- 3. Filters & guards -------------------------------------------------
$c = $renderer->inject_after_content('CONTENT');
assert(strpos($c, 'CONTENT') === 0 && strpos($c, 'toptal-social-share-wrapper') !== false, 'content filter appends');
$t = $renderer->inject_below_title('TITLE');
assert(strpos($t, 'TITLE') === 0 && strpos($t, 'toptal-social-share-wrapper') !== false, 'title filter appends');

$GLOBALS['__post_type'] = 'page'; // pages disabled in migrated settings
assert($renderer->inject_after_content('CONTENT') === 'CONTENT', 'disabled post type skipped');
$GLOBALS['__post_type'] = 'attachment';
assert($renderer->inject_after_content('CONTENT') === 'CONTENT', 'attachment never gets buttons');
$GLOBALS['__post_type'] = 'product';
assert(strpos($renderer->inject_after_content('CONTENT'), 'wrapper') !== false, 'cpt enabled gets buttons');
$GLOBALS['__post_type'] = 'post';

$GLOBALS['post'] = null;
assert($renderer->inject_after_content('CONTENT') === 'CONTENT', 'null post guard content');
assert($renderer->inject_below_title('TITLE') === 'TITLE', 'null post guard title');
assert($renderer->inject_inside_featured_image('IMG', 1) === 'IMG', 'null post guard image');
assert($renderer->shortcode([]) === '', 'null post guard shortcode');
ob_start(); $renderer->render_float_area(); assert(ob_get_clean() === '', 'float area guard');

// Float area renders with wrapper class when post present.
$GLOBALS['post'] = new WP_Post();
ob_start(); $renderer->render_float_area(); $out = ob_get_clean();
assert(strpos($out, 'toptal-social-share-wrapper float-area') !== false, 'float area renders');

// Body class.
assert(in_array('toptal-ss-left-area', $renderer->add_body_class([]), true), 'body class added');

// ---- 4. Shortcode --------------------------------------------------------
$out = $renderer->shortcode(['size' => '"><script>x</script>', 'facebook' => 'yes', 'twitter' => '1']);
assert(strpos($out, '<script>') === false, 'hostile size neutralized');
assert(strpos($out, 'class="facebook') === false, 'facebook=yes not enabled');
assert(strpos($out, 'class="twitter small"') !== false, 'twitter=1 enabled, size fallback');

// ---- 5. Settings sanitizer -----------------------------------------------
$settings_ui = new TopTal_SS_Settings();
$clean = $settings_ui->sanitize_settings([
  'networks' => ['facebook' => '1', 'evil' => '1'],
  'post_types' => ['post' => 1],
  'size' => 'LARGE', 'appearance' => '99', 'default_colors' => '1',
  'colors' => ['facebook' => ['bg' => 'javascript:alert(1)', 'text' => '#abc']],
]);
assert($clean['networks']['facebook'] === 1 && !isset($clean['networks']['evil']), 'unknown network dropped');
assert($clean['size'] === 'large' && $clean['appearance'] === 3, 'size normalized, bad appearance falls back');
assert($clean['colors']['facebook']['bg'] === '' && $clean['colors']['facebook']['text'] === '#abc', 'hostile color rejected, valid kept');
$expected_empty = TopTal_SS_Options::defaults();
$expected_empty['default_colors'] = 0; // unchecked checkbox submits nothing
assert($settings_ui->sanitize_settings('not-an-array') === $expected_empty, 'non-array input yields unchecked defaults');

// ---- 6. AJAX endpoint ----------------------------------------------------
$ajax = new TopTal_SS_Ajax();
function call_ajax($ajax) {
  try { $ajax->update_share_count(); } catch (JsonResponse $r) { return $r; }
  return null;
}
$_SERVER['REMOTE_ADDR'] = '203.0.113.5';

$_POST = ['post_id' => 42, 'network' => 'facebook'];
try { $ajax->update_share_count(); assert(false, 'nonce should be required'); }
catch (RuntimeException $e) { assert($e->getMessage() === 'NONCE_FAIL', 'nonce rejected'); }

$_POST = ['post_id' => 42, 'network' => 'myspace', 'nonce' => 'test-nonce'];
$r = call_ajax($ajax); assert($r && !$r->ok && $r->status === 400, 'bad network -> 400');

$GLOBALS['__post_status'] = 'draft';
$_POST = ['post_id' => 42, 'network' => 'facebook', 'nonce' => 'test-nonce'];
$r = call_ajax($ajax); assert($r && !$r->ok && $r->status === 404, 'draft -> 404');
$GLOBALS['__post_status'] = 'publish';

$r = call_ajax($ajax);
assert($r && $r->ok && $r->payload === 1, 'valid share counted');
assert($GLOBALS['__post_meta'][42]['toptal_ss_share_counts']['total'] === 1, 'total updated');

$r = call_ajax($ajax); assert($r && !$r->ok && $r->status === 429, 'throttled');
$_POST['network'] = 'twitter';
$r = call_ajax($ajax); assert($r && $r->ok, 'different network not throttled');

// ---- 6b. New networks ----------------------------------------------------
$stored = get_option('toptal_ss_settings');
$stored['networks']['reddit'] = 1;
$stored['networks']['email'] = 1;
$stored['networks']['copylink'] = 1;
$stored['appearance'] = 3;
update_option('toptal_ss_settings', $stored);
TopTal_SS_Options::flush_cache();
$html = $renderer->buttons_html(42);
assert(strpos($html, 'reddit.com/submit?url=http%3A%2F%2F') !== false, 'reddit url encoded');
assert(strpos($html, 'href="mailto:?body=http%3A%2F%2F') !== false, 'email mailto link');
assert(preg_match('/<a[^>]*data-toptal-action="copy"[^>]*href="http:\/\/example\.test/', $html) === 1, 'copy link carries raw permalink and copy action');
assert(preg_match('/<a[^>]*target="_blank"[^>]*data-toptal-action="copy"/', $html) === 0, 'copy link does not open a new tab');
assert(strpos($html, 'fa-reddit') !== false && strpos($html, 'fa-envelope') !== false && strpos($html, 'fa-link') !== false, 'new network icons');

// New networks are accepted by the AJAX whitelist automatically.
$_POST = ['post_id' => 42, 'network' => 'reddit', 'nonce' => 'test-nonce'];
$r = call_ajax($ajax); assert($r && $r->ok && $r->payload === 1, 'reddit share counted');

// ---- 7. Fresh-install defaults -------------------------------------------
$GLOBALS['__options'] = [];
TopTal_SS_Options::flush_cache();
TopTal_SS_Options::install_defaults();
$s = TopTal_SS_Options::get(true);
assert($s['networks']['facebook'] === 1 && $s['networks']['pinterest'] === 0, 'fresh defaults enable main networks');
assert($s['locations']['after_post_content'] === 1 && $s['post_types']['post'] === 1, 'fresh defaults enable a location');
TopTal_SS_Options::install_defaults(); // idempotent
assert(TopTal_SS_Options::get(true) === $s, 'install_defaults idempotent');

echo "ALL SMOKE TESTS PASSED\n";
