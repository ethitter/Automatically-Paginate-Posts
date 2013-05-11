=== Automatically Paginate Posts ===
Contributors: ethitter, thinkoomph
Donate link:
Tags: paginate, nextpage, Quicktag
Requires at least: 3.4
Tested up to: 3.6
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically paginate posts by inserting the &lt;!--nextpage--&gt; Quicktag into WordPress posts, pages, or custom post type content.

== DESCRIPTION ==

Automatically paginate WordPress content by inserting the &lt;!--nextpage--&gt; Quicktag.

Option is provided to control what post types are automatically paginated (default is just `post`). Supports `post`, `page`, and any public custom post types.

Option is also provided to specify how many pages content should be broken out over.

== Installation ==

1. Upload automatically-paginate-posts to /wp-content/plugins/.
2. Activate plugin through the WordPress Plugins menu.
3. Configure plugin by going to Settings > Reading.

== Frequently Asked Questions ==

= Where do I set the plugin's options =
The plugin's options are added to the built-in **Reading** settings page in WordPress.

= Can I disable the plugin's functionality for specific posts, pages, or custom post type objects? =
Yes, the plugin adds a metabox to individual items in supported post types that allows the autopaging to be disabled on a per-post basis.

= How can I add support for my custom post type? =
Navigate to Settings > Reading in WP Admin to enable this plugin for your custom post type.

You can also use the filter `autopaging_post_types` to add support by appending your post type's name to the array.

= What filters does this plugin include? =
* `autopaging_post_types` - modify the post types supported by this plugin. Will override the values set under Settings > Reading.
* `autopaging_num_pages_default` - modify the default number of pages over which a post is displayed. Will override the value set under Settings > Reading.
* `autopaging_max_num_pages` - override the maximum number of pages available in the settings page dropdown.
* `autopaging_num_pages` - change the number of pages content is displayed on at runtime. Filter provides access to the full post object in addition to the number of pages.

== Changelog ==

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.1 =
Initial release