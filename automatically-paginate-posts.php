<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Automatically inserts the &lt;!--nextpage--&gt; Quicktag into WordPress posts, pages, or custom post type content.
 *
 * Plugin Name: Automatically Paginate Posts
 * Plugin URI: http://www.oomphinc.com/plugins-modules/automatically-paginate-posts/
 * Description: Automatically inserts the &lt;!--nextpage--&gt; Quicktag into WordPress posts, pages, or custom post type content.
 * Version: 0.3.1
 * Author: Erick Hitter & Oomph, Inc.
 * Author URI: http://www.oomphinc.com/
 * Text Domain: automatically-paginate-posts
 * Domain Path: /languages/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package automatically-paginate-posts
 */

require_once dirname( __FILE__ ) . '/inc/class-block-editor.php';

/**
 * Class Automatically_Paginate_Posts.
 */
class Automatically_Paginate_Posts {
	/**
	 * WordPress Quicktag that creates pagination.
	 */
	const QUICKTAG = '<!--nextpage-->';

	/**
	 * String length of nextpage Quicktag.
	 */
	const QUICKTAG_LENGTH = 15;

	/**
	 * Supported post types.
	 *
	 * @var array
	 */
	private $post_types;

	/**
	 * Default supported post types.
	 *
	 * @var array
	 */
	private $post_types_default = array( 'post' );

	/**
	 * Desired number of pages to split to.
	 *
	 * @var int
	 */
	private $num_pages;

	/**
	 * Method for splitting content, either words or desired number of pages.
	 *
	 * @var string
	 */
	private $paging_type_default = 'pages';

	/**
	 * Default number of pages to split to.
	 *
	 * @var int
	 */
	private $num_pages_default = 2;

	/**
	 * Desired number of words per pages.
	 *
	 * @var int
	 */
	private $num_words;

	/**
	 * Default number of words to split on.
	 *
	 * @var string|int
	 */
	private $num_words_default = '';

	/**
	 * When splitting by word counts, these blocks are considered. Tags are
	 * stripped and remaining content is counted.
	 *
	 * @var array
	 */
	private $supported_block_types_for_word_counts = array(
		'core/paragraph',
	);

	/**
	 * Allowed split types.
	 *
	 * @var array
	 */
	private $paging_types_allowed = array( 'pages', 'words' );

	/**
	 * Supported-post-types option name.
	 *
	 * @var string
	 */
	private $option_name_post_types = 'autopaging_post_types';

	/**
	 * Split-type option name.
	 *
	 * @var string
	 */
	private $option_name_paging_type = 'pages';

	/**
	 * Option holding number of pages to split to.
	 *
	 * @var string
	 */
	private $option_name_num_pages = 'autopaging_num_pages';

	/**
	 * Option holding number of words to split on.
	 *
	 * @var string
	 */
	private $option_name_num_words = 'autopaging_num_words';

	/**
	 * Meta key used to indicate that a post shouldn't be automatically split.
	 *
	 * @var string
	 */
	private $meta_key_disable_autopaging = '_disable_autopaging';

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->setup_hooks();

		new Automatically_Paginate_Posts\Block_Editor( $this );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'action_init' ) );

		// Admin settings.
		register_uninstall_hook( __FILE__, array( 'Automatically_Paginate_Posts', 'uninstall' ) );
		add_filter( 'plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );

		// Post-type settings.
		add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );
		add_filter( 'the_posts', array( $this, 'filter_the_posts' ) );
	}
	/**
	 * Allow access to meta key for disabling autopaging.
	 *
	 * @param string $name Property name.
	 * @return array|string|null
	 */
	public function __get( $name ) {
		if ( 'meta_key' === $name ) {
			return $this->meta_key_disable_autopaging;
		}

		if ( 'post_types' === $name ) {
			if ( ! did_action( 'init' ) ) {
				_doing_it_wrong(
					__METHOD__,
					esc_html__(
						'Post types can only be retrieved after the "init" hook.',
						'automatically-paginate-posts'
					),
					'0.3'
				);

				return null;
			}

			return $this->post_types;
		}

		return null;
	}

	/**
	 * Prevent setting properties.
	 *
	 * @param string $name  Property name.
	 * @param string $value Property value.
	 * @return false
	 */
	public function __set( $name, $value ) {
		return false;
	}

	/**
	 * Indicate if a property is set.
	 *
	 * @param string $name Property name.
	 * @return bool
	 */
	public function __isset( $name ) {
		if ( 'meta_key' === $name ) {
			return true;
		}

		if ( 'post_types' === $name ) {
			return did_action( 'init' );
		}

		return false;
	}

	/**
	 * Load plugin translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'automatically-paginate-posts',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);
	}

	/**
	 * Set post types this plugin can act on, either from Reading page or via filter.
	 * Also sets default number of pages to break content over, either from Reading page or via filter.
	 *
	 * @uses apply_filters, get_option
	 * @action init
	 * @return void
	 */
	public function action_init() {
		// Post types.
		$this->post_types = apply_filters( 'autopaging_post_types', get_option( $this->option_name_post_types, $this->post_types_default ) );

		// Number of pages to break over.
		$this->num_pages = absint( apply_filters( 'autopaging_num_pages_default', get_option( $this->option_name_num_pages, $this->num_pages_default ) ) );
		if ( 0 == $this->num_pages ) {
			$this->num_pages = $this->num_pages_default;
		}

		// Number of words to break over.
		$this->num_words = absint( apply_filters( 'autopaging_num_words_default', get_option( $this->option_name_num_words, $this->num_words_default ) ) );
		if ( 0 == $this->num_words ) {
			$this->num_words = $this->num_words_default;
		}

		// Supported blocks for splitting by words.
		$this->supported_block_types_for_word_counts = apply_filters(
			'autopaging_supported_block_types_for_word_counts',
			$this->supported_block_types_for_word_counts
		);
	}

	/**
	 * Delete plugin settings when uninstalled.
	 * Options names here must match those defined in Class Variables section above.
	 *
	 * @uses delete_option
	 * @action uninstall
	 * @return void
	 */
	public function uninstall() {
		delete_option( 'autopaging_post_types' );
		delete_option( 'autopaging_paging_type' );
		delete_option( 'autopaging_num_pages' );
		delete_option( 'autopaging_num_words' );
	}

	/**
	 * Add settings link to plugin's row actions
	 *
	 * @param array  $actions Plugin's actions.
	 * @param string $file    Plugin filename.
	 * @filter plugin_action_links,
	 */
	public function filter_plugin_action_links( $actions, $file ) {
		if ( false !== strpos( $file, basename( __FILE__ ) ) ) {
			$actions['settings'] = '<a href="' . admin_url( 'options-reading.php' ) . '">Settings</a>';
		}

		return $actions;
	}

	/**
	 * Register settings and settings sections.
	 * Settings appear on the Reading page.
	 *
	 * @uses register_setting, add_settings_section, __, __return_false, add_settings_field
	 * @action admin_init
	 * @return void
	 */
	public function action_admin_init() {
		register_setting( 'reading', $this->option_name_post_types, array( $this, 'sanitize_supported_post_types' ) );
		register_setting( 'reading', $this->option_name_paging_type, array( $this, 'sanitize_paging_type' ) );
		register_setting( 'reading', $this->option_name_num_pages, array( $this, 'sanitize_num_pages' ) );
		register_setting( 'reading', $this->option_name_num_words, array( $this, 'sanitize_num_words' ) );

		add_settings_section( 'autopaging', __( 'Automatically Paginate Posts', 'automatically-paginate-posts' ), '__return_false', 'reading' );
		add_settings_field( 'autopaging-post-types', __( 'Supported post types:', 'automatically-paginate-posts' ), array( $this, 'settings_field_post_types' ), 'reading', 'autopaging' );
		add_settings_field( 'autopaging-paging-type', __( 'Split post by:', 'automatically-paginate-posts' ), array( $this, 'settings_field_paging_type' ), 'reading', 'autopaging' );
	}

	/**
	 * Render post types options.
	 *
	 * @uses get_post_types, get_option, esc_attr, checked, esc_html
	 * @return void
	 */
	public function settings_field_post_types() {
		// Get all public post types.
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		unset( $post_types['attachment'] );

		// Current settings.
		$current_types = get_option( $this->option_name_post_types, $this->post_types_default );

		// Output checkboxes.
		foreach ( $post_types as $post_type => $atts ) :
			?>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name_post_types ); ?>[]" id="post-type-<?php echo esc_attr( $post_type ); ?>" value="<?php echo esc_attr( $post_type ); ?>"<?php checked( in_array( $post_type, $current_types ) ); ?> /> <label for="post-type-<?php echo esc_attr( $post_type ); ?>"><?php echo esc_html( $atts->label ); ?></label><br />
			<?php
		endforeach;
	}

	/**
	 * Sanitize post type inputs.
	 *
	 * @param array $post_types_checked Selected post types to sanitize.
	 * @uses get_post_types
	 * @return array
	 */
	public function sanitize_supported_post_types( $post_types_checked ) {
		$post_types_sanitized = array();

		// Ensure that only existing, public post types are submitted as valid options.
		if ( is_array( $post_types_checked ) && ! empty( $post_types_checked ) ) {
			// Get all public post types.
			$post_types = get_post_types(
				array(
					'public' => true,
				)
			);

			unset( $post_types['attachment'] );

			// Check input post types against those registered with WordPress and made available to this plugin.
			foreach ( $post_types_checked as $post_type ) {
				if ( array_key_exists( $post_type, $post_types ) ) {
					$post_types_sanitized[] = $post_type;
				}
			}
		}

		return $post_types_sanitized;
	}

	/**
	 * Render option to choose paging type and options for that type.
	 *
	 * @uses get_option()
	 * @uses esc_attr()
	 * @uses checked()
	 * @return void
	 */
	public function settings_field_paging_type() {
		$paging_type = get_option( $this->option_name_paging_type, $this->paging_type_default );
		if ( ! in_array( $paging_type, $this->paging_types_allowed ) ) {
			$paging_type = $this->paging_type_default;
		}

		$labels = array(
			'pages' => __( 'Total number of pages:', 'automatically-paginate-posts' ),
			'words' => __( 'Approximate words per page:', 'automatically-paginate-posts' ),
		);

		foreach ( $this->paging_types_allowed as $type ) :
			$func = 'settings_field_num_' . $type;
			?>
			<p>
				<input type="radio" name="<?php echo esc_attr( $this->option_name_paging_type ); ?>" id="autopaging-type-<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $type ); ?>"<?php checked( $type, $paging_type ); ?> />
				<label for="autopaging-type-<?php echo esc_attr( $type ); ?>">
					<?php echo esc_html( $labels[ $type ] ); ?>
					&nbsp;
					<?php $this->{$func}(); ?>
				</label>
			</p>
			<br />
			<?php
		endforeach;
	}

	/**
	 * Validate chosen paging type against allowed values.
	 *
	 * @param string $type Selected paging type.
	 * @return string
	 */
	public function sanitize_paging_type( $type ) {
		return in_array( $type, $this->paging_types_allowed, true ) ? $type : $this->paging_type_default;
	}

	/**
	 * Render dropdown for choosing number of pages to break content over.
	 *
	 * @uses get_option, apply_filters, esc_attr, selected
	 * @return void
	 */
	public function settings_field_num_pages() {
		$num_pages = get_option( $this->option_name_num_pages, $this->num_pages_default );
		$max_pages = apply_filters( 'autopaging_max_num_pages', 10 );

		?>
			<select name="<?php echo esc_attr( $this->option_name_num_pages ); ?>">
				<?php for ( $i = 2; $i <= $max_pages; $i++ ) : ?>
					<option value="<?php echo intval( $i ); ?>"<?php selected( (int) $i, (int) $num_pages ); ?>><?php echo intval( $i ); ?></option>
				<?php endfor; ?>
			</select>
		<?php
	}

	/**
	 * Sanitize number of pages input.
	 *
	 * @param int $num_pages Number of pages to split to.
	 * @uses apply_filters
	 * @return int
	 */
	public function sanitize_num_pages( $num_pages ) {
		return max( 2, min( intval( $num_pages ), apply_filters( 'autopaging_max_num_pages', 10 ) ) );
	}

	/**
	 * Render input field for specifying approximate number of words each page should contain.
	 *
	 * @uses get_option, apply_filters, esc_attr, selected
	 * @return void
	 */
	public function settings_field_num_words() {
		$num_words = apply_filters( 'autopaging_num_words', get_option( $this->option_name_num_words ) )
		?>
			<input
				name="<?php echo esc_attr( $this->option_name_num_words ); ?>"
				value="<?php echo esc_attr( $num_words ); ?>"
				class="small-text"
				type="number"
				step="1"
				min="1"
			/>

			<p class="description"><?php esc_html_e( 'If chosen, each page will contain approximately this many words, depending on paragraph lengths.', 'automatically-paginate-posts' ); ?></p>
		<?php
	}

	/**
	 * Sanitize number of words input. No fewer than 10 by default, filterable by `autopaging_max_num_words`.
	 *
	 * @param int $num_words Number of words to split on.
	 * @uses apply_filters
	 * @return int
	 */
	public function sanitize_num_words( $num_words ) {
		$num_words = absint( $num_words );

		if ( ! $num_words ) {
			return 0;
		}

		return max( $num_words, apply_filters( 'autopaging_min_num_words', 10 ) );
	}

	/**
	 * Add autopaging metabox.
	 *
	 * @uses add_metabox, __
	 * @action add_meta_box
	 * @return void
	 */
	public function action_add_meta_boxes() {
		foreach ( $this->post_types as $post_type ) {
			if (
				function_exists( 'use_block_editor_for_post_type' ) &&
				use_block_editor_for_post_type( $post_type )
			) {
				continue;
			}

			add_meta_box( 'autopaging', __( 'Autopaging', 'automatically-paginate-posts' ), array( $this, 'meta_box_autopaging' ), $post_type, 'side' );
		}
	}

	/**
	 * Render autopaging metabox.
	 *
	 * @param object $post Post object.
	 * @uses esc_attr, checked, _e, __, wp_nonce_field
	 * @return void
	 */
	public function meta_box_autopaging( $post ) {
		?>
		<p>
			<input type="checkbox" name="<?php echo esc_attr( $this->meta_key_disable_autopaging ); ?>" id="<?php echo esc_attr( $this->meta_key_disable_autopaging ); ?>_checkbox" value="1"<?php checked( (bool) get_post_meta( $post->ID, $this->meta_key_disable_autopaging, true ) ); ?> />
			<label for="<?php echo esc_attr( $this->meta_key_disable_autopaging ); ?>_checkbox">
				<?php esc_html_e( 'Disable autopaging for this post?', 'automatically-paginate-posts' ); ?>
			</label>
		</p>
		<p class="description"><?php esc_html__( 'Check the box above to prevent this post from automatically being split over multiple pages.', 'automatically-paginate-posts' ); ?></p>
		<p class="description">
			<?php
				printf(
					/* translators: 1. Quicktag code example. */
					esc_html__(
						'Note that if the %1$s Quicktag is used to manually page this post, automatic paging won\'t be applied, regardless of the setting above.',
						'automatically-paginate-posts'
					),
					// No need to escape a class constant.
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'<code>' . htmlentities( static::QUICKTAG, ENT_QUOTES ) . '</code>'
				);
			?>
		</p>

		<?php
		wp_nonce_field( $this->meta_key_disable_autopaging, $this->meta_key_disable_autopaging . '_wpnonce' );
	}

	/**
	 * Save autopaging metabox.
	 *
	 * @param int $post_id Post ID.
	 * @uses DOING_AUTOSAVE, wp_verify_nonce, update_post_meta, delete_post_meta
	 * @action save_post
	 * @return null
	 */
	public function action_save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if (
			function_exists( 'use_block_editor_for_post' )
			&& use_block_editor_for_post( $post_id )
		) {
			return;
		}

		if ( isset( $_POST[ $this->meta_key_disable_autopaging . '_wpnonce' ] ) && wp_verify_nonce( $_POST[ $this->meta_key_disable_autopaging . '_wpnonce' ], $this->meta_key_disable_autopaging ) ) {
			$disable = isset( $_POST[ $this->meta_key_disable_autopaging ] );

			if ( $disable ) {
				update_post_meta( $post_id, $this->meta_key_disable_autopaging, true );
			} else {
				delete_post_meta( $post_id, $this->meta_key_disable_autopaging );
			}
		}
	}

	/**
	 * Automatically page posts by injecting <!--nextpage--> Quicktag.
	 * Only applied if the post type matches specified options and post doesn't already contain the Quicktag.
	 *
	 * @param array $posts Array of posts retrieved by WP_Query.
	 * @uses is_admin, get_post_meta, absint, apply_filters
	 * @filter the_posts
	 * @return array
	 */
	public function filter_the_posts( $posts ) {
		if ( is_admin() ) {
			return $posts;
		}

		$paging_type = get_option(
			$this->option_name_paging_type,
			$this->paging_type_default
		);

		foreach ( $posts as &$the_post ) {
			if (
				! in_array(
					$the_post->post_type,
					$this->post_types,
					true
				)
			) {
				continue;
			}

			if (
				preg_match(
					'#' . static::QUICKTAG . '#i',
					$the_post->post_content
				)
			) {
				continue;
			}

			if (
				(bool) get_post_meta(
					$the_post->ID,
					$this->meta_key_disable_autopaging,
					true
				)
			) {
				continue;
			}

			$num_pages = absint(
				apply_filters(
					'autopaging_num_pages',
					absint( $this->num_pages ),
					$the_post
				)
			);
			$num_words = absint(
				apply_filters(
					'autopaging_num_words',
					absint( $this->num_words ),
					$the_post
				)
			);

			if ( $num_pages < 2 && empty( $num_words ) ) {
				continue;
			}

			if (
				function_exists( 'has_blocks' )
				&& has_blocks( $the_post )
			) {
				$this->filter_block_editor_post(
					$the_post,
					$paging_type,
					$num_words,
					$num_pages
				);
			} else {
				$this->filter_classic_editor_post(
					$the_post,
					$paging_type,
					$num_words,
					$num_pages
				);
			}
		}

		return $posts;
	}

	/**
	 * Add pagination Quicktag to post authored in the Classic Editor.
	 *
	 * @param WP_Post|object $the_post    Post object.
	 * @param string         $paging_type How to split post.
	 * @param int            $num_words   Number of words to split on.
	 * @param int            $num_pages   Number of pages to split to.
	 * @return void
	 */
	protected function filter_classic_editor_post(
		&$the_post,
		$paging_type,
		$num_words,
		$num_pages
	) {
		// Start with post content, but alias to protect the raw content.
		$content = $the_post->post_content;

		// Normalize post content to simplify paragraph counting and automatic paging. Accounts for content that hasn't been cleaned up by TinyMCE.
		$content = preg_replace( '#<p>(.+?)</p>#i', "$1\r\n\r\n", $content );
		$content = preg_replace( '#<br(\s*/)?>#i', "\r\n", $content );
		$content = explode( "\r\n\r\n", $content );

		// Count number of paragraphs content was exploded to.
		$count = count( $content );

		// Nothing to do, goodbye.
		if ( $count <= 1 ) {
			return;
		}

		switch ( $paging_type ) {
			case 'words':
				$word_counter = 0;

				// Count words per paragraph and break after the paragraph that exceeds the set threshold.
				foreach ( $content as $index => $paragraph ) {
					$word_counter += mb_strlen(
						wp_strip_all_tags(
							$paragraph
						)
					);

					if ( $word_counter >= $num_words ) {
						$content[ $index ] .= static::QUICKTAG;
						$word_counter = 0;
					}
				}

				// Prevent the last page from being empty.
				$last_page = array_pop( $content );
				if (
					static::QUICKTAG ===
						substr(
							$last_page,
							- static::QUICKTAG_LENGTH
						)
					) {
					$content[] = substr(
						$last_page,
						0,
						strlen( $last_page ) - static::QUICKTAG_LENGTH
					);
				} else {
					$content[] = $last_page;
				}

				break;

			case 'pages':
			default:
				$frequency = $this->get_insertion_frequency_by_pages(
					$count,
					$num_pages
				);

				$i = 1;

				// Loop through content pieces and append Quicktag as is appropriate.
				foreach ( $content as $key => $value ) {
					if ( $this->is_at_end_for_pages( $key, $count ) ) {
						break;
					}

					if (
						$this->is_insertion_point_for_pages(
							$key,
							$i,
							$frequency
						)
					) {
						$content[ $key ] .= static::QUICKTAG;
						$i++;
					}
				}

				break;
		}

		// Reunite content.
		$content = implode( "\r\n\r\n", $content );

		// And, overwrite the original content.
		$the_post->post_content = $content;
	}

	/**
	 * Add pagination block to post authored in the Block Editor.
	 *
	 * @param WP_Post $the_post    Post object.
	 * @param string  $paging_type How to split post.
	 * @param int     $num_words   Number of words to split on.
	 * @param int     $num_pages   Number of pages to split to.
	 * @return void
	 */
	protected function filter_block_editor_post(
		&$the_post,
		$paging_type,
		$num_words,
		$num_pages
	) {
		$blocks     = parse_blocks( $the_post->post_content );
		$new_blocks = array();

		switch ( $paging_type ) {
			case 'words':
				$word_count = 0;

				foreach ( $blocks as $block ) {
					$new_blocks[] = $block;

					if (
						in_array(
							$block['blockName'],
							$this->supported_block_types_for_word_counts,
							true
						)
					) {
						$word_count += mb_strlen(
							trim(
								wp_strip_all_tags(
									$block['innerHTML']
								)
							)
						);

						if ( $word_count >= $num_words ) {
							$new_blocks[] = $this->get_parsed_nextpage_block();

							$word_count = 0;
						}
					}
				}

				$last_block = array_pop( $new_blocks );
				if ( $this->get_parsed_nextpage_block() !== $last_block ) {
					$new_blocks[] = $last_block;
				}
				break;

			case 'pages':
			default:
				$count = count( $blocks );

				$frequency = $this->get_insertion_frequency_by_pages(
					$count,
					$num_pages
				);

				$i = 1;

				foreach ( $blocks as $key => $block ) {
					$new_blocks[] = $block;

					if ( $this->is_at_end_for_pages( $key, $count ) ) {
						break;
					}

					if (
						$this->is_insertion_point_for_pages(
							$key,
							$i,
							$frequency
						)
					) {
						$new_blocks[] = $this->get_parsed_nextpage_block();
						$i++;
					}
				}
				break;
		}

		$the_post->post_content = serialize_blocks( $new_blocks );
	}

	/**
	 * Determine after how many paragraphs a page break should be inserted.
	 *
	 * @param int $count     Total number of paragraphs.
	 * @param int $num_pages Desired number of pages.
	 * @return int
	 */
	protected function get_insertion_frequency_by_pages( $count, $num_pages ) {
		$frequency = (int) round( $count / $num_pages );

		// If number of pages is greater than number of paragraphs, put each paragraph on its own page.
		if ( $num_pages > $count ) {
			$frequency = 1;
		}

		return $frequency;
	}

	/**
	 * Determine if more page breaks should be inserted.
	 *
	 * @param int $key   Current position in array of blocks.
	 * @param int $count Total number of paragraphs.
	 * @return bool
	 */
	protected function is_at_end_for_pages( $key, $count ) {
		return ( $key + 1 ) === $count;
	}

	/**
	 * Determine if current loop iteration is where a page break is expected.
	 *
	 * @param int $loop_key            Current position in array of blocks.
	 * @param int $insertion_iterator  Current number of page breaks inserted.
	 * @param int $insertion_frequency After this many blocks a should break be
	 *                                 inserted.
	 * @return bool
	 */
	protected function is_insertion_point_for_pages(
		$loop_key,
		$insertion_iterator,
		$insertion_frequency
	) {
		return ( $loop_key + 1 ) ===
			( $insertion_iterator * $insertion_frequency );
	}

	/**
	 * Create parsed representation of block for insertion in list of post's
	 * blocks.
	 *
	 * @return array
	 */
	protected function get_parsed_nextpage_block() {
		static $block;

		if ( ! $block ) {
			$_block = parse_blocks(
				'<!-- wp:nextpage -->
' . static::QUICKTAG . '
<!-- /wp:nextpage -->'
			);

			$block = array_shift( $_block );
		}

		return $block;
	}
}

new Automatically_Paginate_Posts();
?>
