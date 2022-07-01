=== Automatically Paginate Posts ===
Contributors: ethitter, thinkoomph, bendoh
Donate link:
Tags: paginate, nextpage, Quicktag
Requires at least: 3.4
Tested up to: 6.0
Stable tag: 0.3.1
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically paginate posts by inserting the `<!--nextpage-->` Quicktag.

== Description ==

Automatically paginate WordPress content by inserting the `<!--nextpage-->` Quicktag at intervals controlled by plugin's settings.

Option is provided to control which post types are automatically paginated (default is the "Post" post type). Supports any public custom post types (non-public types are supported via the `autopaging_post_types` filter).

Option is also provided to specify how many pages content should be broken out over, or how many words should be included per page.

== Installation ==

1. Upload automatically-paginate-posts to `/wp-content/plugins/`.
2. Activate plugin through the WordPress Plugins menu.
3. Configure plugin by going to **Settings > Reading**.

== Frequently Asked Questions ==

= Where do I set the plugin's options? =
The plugin's options are added to the built-in **Reading** settings page in WordPress.

= Can I disable the plugin's functionality for specific content? =
Yes, the plugin adds a metabox (Classic Editor) and a sidebar component (Block Editor) to individual items in supported post types that allows the autopaging to be disabled on a per-post basis.

= How can I add support for my custom post type? =
Navigate to **Settings > Reading** in WP Admin to enable this plugin for your custom post type.

You can also use the filter `autopaging_post_types` to add support by appending your post type's name to the array.

= What filters does this plugin include? =
* `autopaging_post_types` - modify the post types supported by this plugin. Will override the values set under Settings > Reading.
* `autopaging_num_pages_default` - modify the default number of pages over which a post is displayed. Will override the value set under Settings > Reading.
* `autopaging_max_num_pages` - override the maximum number of pages available in the settings page dropdown when the paging type is "pages".
* `autopaging_max_num_words` - override the minimum number of words allowed per page page when the paging type is "words".
* `autopaging_num_pages` - change the number of pages content is displayed on at runtime. Filter provides access to the full post object in addition to the number of pages.
* `autopaging_num_words` - change the number of words displayed per page at runtime. Filter provides access to the full post object in addition to the number of words.
* `autopaging_supported_block_types_for_word_counts` - specify which block types are considered when splitting a block-editor post by word count.

== Changelog ==

= 0.3.1 =
* Fix translation support.

= 0.3 =
* Add support for content authored in block editor (Gutenberg).
* Add native block-editor control to replace legacy metabox.
* Fix bug that created empty pages.

= 0.2 =
* Allow for number of words to be specified instead of number of pages.

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.3.1 =
Fixes translation support.

= 0.3 =
Add support for block editor and fix bug that created empty pages.

= 0.2 =
Allow for number of words to be specified instead of number of pages.

= 0.1 =
Initial release
