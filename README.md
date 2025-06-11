# TopTal Social Share

TopTal Social Share is a simple WordPress plugin that adds social sharing buttons to posts, pages and custom post types. Buttons can be displayed after the title, inside the featured image, floating on the left side of the screen or after the content.

## Features

- Share links for Facebook, Twitter, LinkedIn, Pinterest and WhatsApp.
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

The plugin code is located in `toptal-social-share.php` and assets under the `assets/` directory. JavaScript and CSS files follow basic WordPress coding standards and end with a newline.

## License

MIT
