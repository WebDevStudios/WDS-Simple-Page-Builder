# WebDevStudios Simple Page Builder

Uses existing template parts in the theme to dynamically build a custom page layout, *per page*. An options page allows you to define your template part directory (if you wanted to keep these template parts separate from other template parts) and the template part prefix you are using.

## Usage

To use this plugin, your theme template files must have the following `do_action` wherever you want the template parts to load:

`<?php do_action( 'wds_page_builder_load_parts' ); ?>`

This will take care of loading the correct template parts in the order you specified.

## Page vs Global Parts

The page builder will, by default, use the template parts that were set on the page when you set them on the Edit page screen. However, if no template parts were defined on the individual page, you can also set Global Template Parts that will load on all pages that don't have their own, individual template parts defined.

You can leave the Global setting to "- No Template Parts -" to not define any global template parts if individual page-specific template parts weren't set.

## Screenshots

Dynamically add template parts within the Edit Page screen and reorder those components.
![page builder ui](https://cldup.com/epETzuW4Dx.gif)

The results are saved to post meta on the page and visible as soon as you save the page.
![page builder front-end](https://cldup.com/djUNBYKcEd.gif)

Options page
![options page](https://cldup.com/VawlJxUjBB-1200x1200.png)

## Changelog

**1.2**
* added saved layouts feature. Now you can save layouts and set those saved layouts as the defaults for post types. Or you can define a specific layout in the `do_action`, e.g. `do_action( 'wds_page_builder_load_parts', 'my named layout' )`

**1.1**
* added post type support beyond just pages. Options page now allows you to check which post types you want to use the page builder on, and the page builder metabox will appear on the Add New/Edit page for those post types.

**1.0.1**
* switched to using get_queried_object instead of get_the_ID to get a post id when checking the existence of post meta for cases when a loop is not being used or the action is fired outside the loop.