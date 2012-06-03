<?php
/*
Plugin Name: Automatically Paginate Posts
Plugin URI:
Description: Automatically inserts the &lt;!--nextpage--&gt; Quicktag into WordPress posts, pages, or custom post type content.
Version: 0.1
Author: Erick Hitter (Oomph, Inc.)
Author URI: http://www.thinkoomph.com/

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
	private $num_pages;

	private $meta_key_disable_autopaging = '_disable_autopaging';

	/**
	 * Register actions and filters
	 *
	 * @uses add_action, add_filter
	 * @return null
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );
		add_filter( 'the_posts', array( $this, 'filter_the_posts' ) );
	}

	/**
	 *
	 */
	public function action_init() {
		//Post types
		$this->post_types = apply_filters( 'autopaging_post_types', array( 'post', 'page' ) );

		//Number of pages to break over
		$this->num_pages = absint( apply_filters( 'autopaging_num_pages_default', 2 ) );
		if ( 0 == $this->num_pages )
			$this->num_pages = 2;
	}

	/**
	 * Add autopaging metabox
	 *
	 * @uses this::get_option, add_metabox
	 * @action add_meta_box
	 * @return null
	 */
	function action_add_meta_boxes() {
		add_meta_box( 'autopaging', 'Post Autopaging', array( $this, 'meta_box_autopaging' ), 'post', 'side' );
	}

	/**
	 * Render autopaging metabox
	 *
	 * @param object $post
	 * @uses esc_attr, checked, wp_nonce_field
	 * @return string
	 */
	function meta_box_autopaging( $post ) {
	?>
		<p>
			<input type="checkbox" name="<?php echo esc_attr( $this->meta_key_disable_autopaging ); ?>" id="<?php echo esc_attr( $this->meta_key_disable_autopaging ); ?>_checkbox" value="1"<?php checked( (bool) get_post_meta( $post->ID, $this->meta_key_disable_autopaging, true ) ); ?> /> <label for="<?php echo esc_attr( $this->meta_key_disable_autopaging ); ?>_checkbox">Disable autopaging for this post?</label>
		</p>
		<p class="description">Check the box above to prevent this post from automatically being split over two pages.</p>
		<p class="description">Note that if the <code>&lt;!--nextpage--&gt;</code> Quicktag is used to manually page this post, automatic paging won't be applied, regardless of the setting above.</p>
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
	function action_save_post( $post_id ) {
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
	 * @uses is_admin, get_post_meta
	 * @filter the_posts
	 * @return array
	 */
	function filter_the_posts( $posts ) {
		if ( ! is_admin() ) {
			foreach( $posts as $the_post ) {
				if ( in_array( $the_post->post_type, $this->post_types ) && ! preg_match( '#<!--nextpage-->#i', $the_post->post_content ) && ! (bool) get_post_meta( $the_post->ID, $this->meta_key_disable_autopaging, true ) ) {
					//In-time filtering of number of pages to break over, based on post data. If value is less than 2, nothing should be done.
					$num_pages = absint( apply_filters( 'autopaging_num_pages', absint( $this->num_pages ), $the_post ) );

					if ( $num_pages < 2 )
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
						//Explode content at double line breaks
						$content = explode( "\r\n\r\n", $content );

						//Determine where to insert Quicktag and insert
						$insert_before = ceil( count( $content ) / $num_pages );

						$content[ $insert_before ] = '<!--nextpage-->' . $content[ $insert_before ];

						//Reunite content
						$content = implode( "\r\n\r\n", $content );

						//And, overwrite the original content
						$the_post->post_content = $content;
					}

					//Lastly, clean up.
					unset( $content );
				}
			}
		}

		return $posts;
	}
}
new Automatically_Paginate_Posts;
?>