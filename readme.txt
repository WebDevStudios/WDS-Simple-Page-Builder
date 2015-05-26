=== WDS Simple Page Builder ===
Contributors: jazzs3quence, webdevstudios
Tags: page builder, template parts, theme
Requires at least: 3.0
Tested up to: 4.2.2
Stable tag: 1.0

Uses existing template parts in the currently-active theme to build a customized page with rearrangeable elements.

== Description ==

Uses existing template parts in the theme to dynamically build a custom page layout, *per page*. An options page allows you to define your template part directory (if you wanted to keep these template parts separate from other template parts) and the template part prefix you are using.

= Usage =

To use this plugin, your theme template files must have the following `do_action` wherever you want the template parts to load:

`<?php do_action( 'wds_page_builder_load_parts' ); ?>`

This will take care of loading the correct template parts in the order you specified.

= Page vs Global Parts =

The page builder will, by default, use the template parts that were set on the page when you set them on the Edit page screen. However, if no template parts were defined on the individual page, you can also set Global Template Parts that will load on all pages that don't have their own, individual template parts defined.

You can leave the Global setting to "- No Template Parts -" to not define any global template parts if individual page-specific template parts weren't set.


== Installation ==

1. Upload the `wds-simple-page-builder` directory to the \`/wp-content/plugins/\` directory or install via the Plugin Installer
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action( 'wds_page_builder_load_parts' ); ?>` in your templates

== Frequently Asked Questions ==



== Screenshots ==

1. Dynamically add template parts within the Edit Page screen and reorder those components.
2. The results are saved to post meta on the page and visible as soon as you save the page.
3. Options page

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

