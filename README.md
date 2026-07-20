# TopTal Social Share

TopTal Social Share is a simple WordPress plugin that adds social sharing buttons to posts, pages and custom post types. Buttons can be displayed after the title, inside the featured image, floating on the left side of the screen or after the content.

## Features

- Share links for Facebook, Twitter, LinkedIn, Pinterest and WhatsApp.
- Tracks how many times each network is shared per post.
- Optional floating share bar on the left side of the page.
- Choose between icon only, text only or icon with text styles.
- Supports small, medium and large button sizes.
- Customise button colours via the WordPress colour picker.
- Enable or disable buttons globally or per post type.
- Shortcode `[toptal_ss]` for manual placement.

## Installation

1. Copy the plugin folder into `wp-content/plugins`.
2. Activate **TopTal Social Share** from the WordPress plugins screen.
3. Navigate to **Settings → TopTal Social Share** to configure which networks and locations to display.

## Shortcode Usage

```
[toptal_ss size="small" facebook="1" twitter="1" linkedin="1" pinterest="0" whatsapp="0"]
```

All attributes are optional. When omitted, the defaults from the settings page are used.

## Development

The plugin bootstrap is `toptal-social-share.php`; the code lives in `includes/` split by concern:

- `class-toptal-ss-networks.php` — network registry (labels, icons, share endpoints). New networks are added here and picked up everywhere automatically; the list is filterable via `toptal_ss_networks`.
- `class-toptal-ss-options.php` — settings storage in a single `toptal_ss_settings` array option, with defaults and a one-time migration from the 1.0 scalar options.
- `class-toptal-ss-settings.php` — the admin settings screen.
- `class-toptal-ss-renderer.php` — frontend markup, content/title/thumbnail filters, floating bar and shortcode.
- `class-toptal-ss-assets.php` — conditional, versioned asset loading.
- `class-toptal-ss-ajax.php` — the nonce-protected, throttled share-count endpoint.

Assets are under `assets/`. JavaScript and CSS files follow basic WordPress coding standards and end with a newline.

## License

MIT

## Buy Me a Coffee

If you find this plugin useful, consider [buying me a coffee](https://buymeacoffee.com/costinbotez).

For more WordPress resources and plugins, visit [Nomad Developer](https://nomad-developer.co.uk/).
