<?php
/*
Plugin Name: Automatically Paginate Posts
Plugin URI: http://www.oomphinc.com/plugins-modules/automatically-paginate-posts/
Description: Automatically inserts the &lt;!--nextpage--&gt; Quicktag into WordPress posts, pages, or custom post type content.
Version: 0.2
Author: Erick Hitter & Oomph, Inc.
Author URI: http://www.oomphinc.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Automatically_Paginate_Posts {
	/**
	 * Class variables
	 */
	private $post_types;
	private $post_types_default = array( 'post' );

	private $num_pages;
	private $num_pages_default = 2;
	private $num_words_default = '';

	//Ensure option names match values in this::uninstall
	private $option_name_post_types = 'autopaging_post_types';
	private $option_name_num_pages = 'autopaging_num_pages';
	private $option_name_num_words = 'autopaging_num_words';

	private $meta_key_disable_autopaging = '_disable_autopaging';

	/**
	 * Register actions and filters
	 *
	 * @uses add_action, register_uninstall_hook, add_filter
	 * @return null
	 */
	public function __construct() {
		//Filters
		add_action( 'init', array( $this, 'action_init' ) );

		//Admin settings
		register_uninstall_hook( __FILE__, array( 'Automatically_Paginate_Posts', 'uninstall' ) );
		add_filter( 'plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );

		//Post-type settings
		add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );
		add_filter( 'the_posts', array( $this, 'filter_the_posts' ) );
	}

	/**
	 * Set post types this plugin can act on, either from Reading page or via filter
	 * Also sets default number of pages to break content over, either from Reading page or via filter
	 *
	 * @uses apply_filters, get_option
	 * @action init
	 * @return null
	 */
	public function action_init() {
		//Post types
		$this->post_types = apply_filters( 'autopaging_post_types', get_option( $this->option_name_post_types, $this->post_types_default ) );

		//Number of pages to break over
		$this->num_pages = absint( apply_filters( 'autopaging_num_pages_default', get_option( $this->option_name_num_pages, $this->num_pages_default ) ) );
		if ( 0 == $this->num_pages )
			$this->num_pages = $this->num_pages_default;

		//Number of words to break over
		$this->num_words = absint( apply_filters( 'autopaging_num_words_default', get_option( $this->option_name_num_words, $this->num_words_default ) ) );
		if ( 0 == $this->num_words )
			$this->num_words = $this->num_words_default;
	}

	/**
	 * Delete plugin settings when uninstalled.
	 * Options names here must match those defined in Class Variables section above.
	 *
	 * @uses delete_option
	 * @action uninstall
	 * @return null
	 */
	public function uninstall() {
		delete_option( 'autopaging_post_types' );
		delete_option( 'autopaging_num_pages' );
		delete_option( 'autopaging_num_words' );
	}

	/**
	 * Add settings link to plugin's row actions
	 *
	 * @param array $actions
	 * @param string $file
	 * @filter plugin_action_links,
	 */
	public function filter_plugin_action_links( $actions, $file ) {
		if ( false !== strpos( $file, basename( __FILE__ ) ) )
			$actions[ 'settings' ] = '<a href="' . admin_url( 'options-reading.php' ) . '">Settings</a>';

		return $actions;
	}

	/**
	 * Register settings and settings sections
	 * Settings appear on the Reading page
	 *
	 * @uses register_setting, add_settings_section, __, __return_false, add_settings_field
	 * @action admin_init
	 * @return null
	 */
	public function action_admin_init() {
		register_setting( 'reading', $this->option_name_post_types, array( $this, 'sanitize_supported_post_types' ) );
		register_setting( 'reading', $this->option_name_num_pages, array( $this, 'sanitize_num_pages' ) );
		register_setting( 'reading', $this->option_name_num_words, array( $this, 'sanitize_num_words' ) );

		add_settings_section( 'autopaging', __( 'Automatically Paginate Posts', 'autopaging' ), '__return_false', 'reading' );
		add_settings_field( 'autopaging-post-types', __( 'Supported post types:', 'autopaging' ), array( $this, 'settings_field_post_types' ), 'reading', 'autopaging' );
		add_settings_field( 'autopaging-num-pages', __( 'Number of pages to split content into:', 'autopaging' ), array( $this, 'settings_field_num_pages' ), 'reading', 'autopaging' );
		add_settings_field( 'autopaging-num-words', __( 'Number of words for each page:', 'autopaging' ), array( $this, 'settings_field_num_words' ), 'reading', 'autopaging' );
	}

	/**
	 * Render post types options
	 *
	 * @uses get_post_types, get_option, esc_attr, checked, esc_html
	 * @return string
	 */
	public function settings_field_post_types() {
		//Get all public post types
		$post_types = get_post_types( array(
			'public' => true
		), 'objects' );

		//Remove attachments
		unset( $post_types[ 'attachment' ] );

		//Current settings
		$current_types = get_option( $this->option_name_post_types, $this->post_types_default );

		//Output checkboxes
		foreach ( $post_types as $post_type => $atts ) :
		?>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name_post_types ); ?>[]" id="post-type-<?php echo esc_attr( $post_type ); ?>" value="<?php echo esc_attr( $post_type ); ?>"<?php checked( in_array( $post_type, $current_types ) ); ?> /> <label for="post-type-<?php echo esc_attr( $post_type ); ?>"><?php echo esc_html( $atts->label ); ?></label><br />
		<?php
		endforeach;
	}

	/**
	 * Sanitize post type inputs
	 *
	 * @param array $post_types_checked
	 * @uses get_post_types
	 * @return array
	 */
	public function sanitize_supported_post_types( $post_types_checked ) {
		$post_types_sanitized = array();

		//Ensure that only existing, public post types are submitted as valid options
		if ( is_array( $post_types_checked ) && ! empty( $post_types_checked ) ) {
			//Get all public post types
			$post_types = get_post_types( array(
				'public' => true
			) );

			//Remove attachments
			unset( $post_types[ 'attachment' ] );

			//Check input post types against those registered with WordPress and made available to this plugin
			foreach ( $post_types_checked as $post_type ) {
				if ( array_key_exists( $post_type, $post_types ) )
					$post_types_sanitized[] = $post_type;
			}
		}

		return $post_types_sanitized;
	}

	/**
	 * Render dropdown for choosing number of pages to break content over
	 *
	 * @uses get_option, apply_filters, esc_attr, selected
	 * @return string
	 */
	public function settings_field_num_pages() {
		$num_pages = get_option( $this->option_name_num_pages, $this->num_pages_default );
		$max_pages = apply_filters( 'autopaging_max_num_pages', 10 );

		?>
			<select name="<?php echo esc_attr( $this->option_name_num_pages ); ?>">
				<?php for( $i = 2; $i <= $max_pages; $i++ ) : ?>
					<option value="<?php echo intval( $i ); ?>"<?php selected( (int) $i, (int) $num_pages ); ?>><?php echo intval( $i ); ?></option>
				<?php endfor; ?>
			</select>
		<?php
	}

	/**
	 * Sanitize number of pages input
	 *
	 * @param int $num_pages
	 * @uses apply_filters
	 * @return int
	 */
	public function sanitize_num_pages( $num_pages ) {
		return max( 2, min( intval( $num_pages ), apply_filters( 'autopaging_max_num_pages', 10 ) ) );
	}

	/**
	 * Render dropdown for choosing number of pages to break content over
	 *
	 * @uses get_option, apply_filters, esc_attr, selected
	 * @return string
	 */
	public function settings_field_num_words() {
		$num_words = apply_filters( 'autopaging_num_words', get_option( $this->option_name_num_words ) )
		?>
			<input name="<?php echo esc_attr( $this->option_name_num_words ); ?>" value="<?php echo esc_attr( $num_words ); ?>" size="4" />
	
			<p class="description">If set, each page will contain approximately this many words, more or less depending on paragraph lengths, and Number of pages will be ignored.</p>
		<?php
	}

	/**
	 * Sanitize number of words input. No fewer than 10 (by default, filterable by autopaging_min_num_words)
	 *
	 * @param int $numwords
	 * @uses apply_filters
	 * @return int
	 */
	public function sanitize_num_words( $num_words ) {
		$num_words = apply_filters( 'autopaging_num_words', $num_words );

		if( empty( $num_words ) )
			return '';	

		return max( 1, (int) $num_words );
	}

	/**
	 * Add autopaging metabox
	 *
	 * @uses add_metabox, __
	 * @action add_meta_box
	 * @return null
	 */
	public function action_add_meta_boxes() {
		foreach ( $this->post_types as $post_type ) {
			add_meta_box( 'autopaging', __( 'Post Autopaging', 'autopaging' ), array( $this, 'meta_box_autopaging' ), $post_type, 'side' );
		}
	}

	/**
	 * Render autopaging metabox
	 *
	 * @param object $post
	 * @uses esc_attr, checked, _e, __, wp_nonce_field
	 * @return string
	 */
	public function meta_box_autopaging( $post ) {
	?>
		<p>
			<input type="checkbox" name="<?php echo esc_attr( $this->meta_key_disable_autopaging ); ?>" id="<?php echo esc_attr( $this->meta_key_disable_autopaging ); ?>_checkbox" value="1"<?php checked( (bool) get_post_meta( $post->ID, $this->meta_key_disable_autopaging, true ) ); ?> /> <label for="<?php echo esc_attr( $this->meta_key_disable_autopaging ); ?>_checkbox">Disable autopaging for this post?</label>
		</p>
		<p class="description"><?php _e( 'Check the box above to prevent this post from automatically being split over multiple pages.', 'autopaging' ); ?></p>
		<p class="description"><?php printf( __( 'Note that if the %1$s Quicktag is used to manually page this post, automatic paging won\'t be applied, regardless of the setting above.', 'autopaging' ), '<code>&lt;!--nextpage--&gt;</code>' ); ?></p>
	<?php
		wp_nonce_field( $this->meta_key_disable_autopaging, $this->meta_key_disable_autopaging . '_wpnonce' );
	}

	/**
	 * Save autopaging metabox
	 *
	 * @param int $post_id
	 * @uses DOING_AUTOSAVE, wp_verify_nonce, update_post_meta, delete_post_meta
	 * @action save_post
	 * @return null
	 */
	public function action_save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( isset( $_POST[ $this->meta_key_disable_autopaging . '_wpnonce' ] ) && wp_verify_nonce( $_POST[ $this->meta_key_disable_autopaging . '_wpnonce' ], $this->meta_key_disable_autopaging ) ) {
			$disable = isset( $_POST[ $this->meta_key_disable_autopaging ] ) ? true : false;

			if ( $disable )
				update_post_meta( $post_id, $this->meta_key_disable_autopaging, true );
			else
				delete_post_meta( $post_id, $this->meta_key_disable_autopaging );
		}
	}

	/**
	 * Automatically page posts by injecting <!--nextpage--> Quicktag.
	 * Only applied if the post type matches specified options and post doesn't already contain the Quicktag.
	 *
	 * @param array $posts
	 * @uses is_admin, get_post_meta, absint, apply_filters
	 * @filter the_posts
	 * @return array
	 */
	public function filter_the_posts( $posts ) {
		if ( ! is_admin() ) {
			foreach( $posts as $the_post ) {
				if ( in_array( $the_post->post_type, $this->post_types ) && ! preg_match( '#<!--nextpage-->#i', $the_post->post_content ) && ! (bool) get_post_meta( $the_post->ID, $this->meta_key_disable_autopaging, true ) ) {
					//In-time filtering of number of pages to break over, based on post data. If value is less than 2, nothing should be done.
					$num_pages = absint( apply_filters( 'autopaging_num_pages', absint( $this->num_pages ), $the_post ) );
					$num_words = absint( apply_filters( 'autopaging_num_words', absint( $this->num_words ), $the_post ) );

					if ( $num_pages < 2 && empty( $num_words ) )
						continue;

					//Start with post content, but alias to protect the raw content.
					$content = $the_post->post_content;

					//Normalize post content to simplify paragraph counting and automatic paging. Accounts for content that hasn't been cleaned up by TinyMCE.
					$content = preg_replace( '#<p>(.+?)</p>#i', "$1\r\n\r\n", $content );
					$content = preg_replace( '#<br(\s*/)?>#i', "\r\n", $content );

					//Count paragraphs
					$count = preg_match_all( '#\r\n\r\n#', $content, $matches );

					//Keep going, if we have something to count.
					if ( is_int( $count ) && 0 < $count ) {
						//Explode content at double (or more) line breaks
						$content = explode( "\r\n\r\n", $content );

						//Aggregate paragraphs
						if( !empty( $num_words ) ) {
							$word_counter = 0;

							//Aggregate num_words paged content here
							$aggregate = array();
							$aggregate_index = 0;

							//Collapse together paragraph according to number of words per page
							foreach( $content as $index => $paragraph ) {
								$paragraph_words = count( preg_split( '/\s+/', strip_tags( $paragraph ) ) );

								if( $word_counter + $paragraph_words / 2 >= $num_words ) {
									$aggregate_index++;
									$word_counter = 0;
								}

								if( !isset( $aggregate[$aggregate_index] ) )
									$aggregate[$aggregate_index] = '';

								$aggregate[$aggregate_index] .= $paragraph . "\r\n\r\n";

								$word_counter += $paragraph_words;

								if( $word_counter > $num_words ) {
									$aggregate_index++;
									$word_counter = 0;
								}
							}

							//Pretend the aggregated paragraphs based on max_words
							//are the original set
							$content = $aggregate;

							//Override num_pages
							$num_pages = count( $content );

							unset( $word_counter );
							unset( $aggregate );
							unset( $aggregate_index );
						}

						//Count number of paragraphs content was exploded to
						$count = count( $content );

						//Determine when to insert Quicktag
						$insert_every = $count / $num_pages;
						$insert_every_rounded = round( $insert_every );

						//If number of pages is greater than number of paragraphs, put each paragraph on its own page
						if ( $num_pages > $count )
							$insert_every_rounded = 1;

						//Set initial counter position.
						$i = $count - 1 == $num_pages ? 2 : 1;

						//Loop through content pieces and append Quicktag as is appropriate
						foreach( $content as $key => $value ) {
							if ( $key + 1 == $count )
								break;

							if ( ( $key + 1 ) == ( $i * $insert_every_rounded ) ) {
								$content[ $key ] = $content[ $key ] . '<!--nextpage-->';
								$i++;
							}
						}

						//Reunite content
						$content = implode( "\r\n\r\n", $content );

						//And, overwrite the original content
						$the_post->post_content = $content;

						//Clean up
						unset( $count );
						unset( $insert_every );
						unset( $insert_every_rounded );
						unset( $key );
						unset( $value );
					}

					//Lastly, clean up.
					unset( $num_pages );
					unset( $content );
					unset( $count );
				}
			}
		}

		return $posts;
	}
}
new Automatically_Paginate_Posts;
?>
