<?php
/**
 * Block Editor support.
 *
 * @package automatically-paginate-posts
 */

namespace Automatically_Paginate_Posts;

/**
 * Block_Editor class.
 */
class Block_Editor {
	/**
	 * Instance of plugin's main class.
	 *
	 * For legacy reasons, this is not a singleton, so it cannot be accessed
	 * without passing the instance directly.
	 *
	 * @var \Automatically_Paginate_Posts
	 */
	protected $autopaging_instance;

	/**
	 * Class constructor.
	 *
	 * @param \Automatically_Paginate_Posts $autopaging_instance Instance of
	 *                                                           plugin's main
	 *                                                           class.
	 */
	public function __construct( $autopaging_instance ) {
		if ( ! $autopaging_instance instanceof \Automatically_Paginate_Posts ) {
			return;
		}

		$this->autopaging_instance = $autopaging_instance;

		add_action( 'rest_api_init', array( $this, 'register_meta' ) );
		add_filter( 'is_protected_meta', array( $this, 'allow_meta_updates' ), 10, 3 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
	}

	/**
	 * Register meta for access in Gutenberg.
	 *
	 * @return void
	 */
	public function register_meta() {
		global $wp_version;

		if (
			! function_exists( 'register_meta' )
			|| version_compare( $wp_version, '4.6.0', '<' )
		) {
			return;
		}

		foreach ( $this->autopaging_instance->post_types as $post_type ) {
			register_meta(
				'post',
				$this->autopaging_instance->meta_key,
				array(
					'object_subtype' => $post_type,
					'default'           => false,
					'description'       => __(
						'Whether or not to disable pagination for this post.',
						'automatically-paginate-posts'
					),
					'type'              => 'boolean',
					'sanitize_callback' => static function ( $value ) {
						return (bool) $value;
					},
					'show_in_rest'      => true,
					'single'            => true,
				)
			);
		}
	}

	/**
	 * Allow access to plugin's meta key via the REST API.
	 *
	 * @param bool   $is_protected If meta key is protected.
	 * @param string $meta_key     Meta key nane.
	 * @param string $meta_type    Meta key type.
	 * @return bool
	 */
	public function allow_meta_updates( $is_protected, $meta_key, $meta_type ) {
		if ( 'post' !== $meta_type ) {
			return $is_protected;
		}

		if ( $meta_key === $this->autopaging_instance->meta_key ) {
			return false;
		}

		return $is_protected;
	}

	/**
	 * Enqueue block-editor assets.
	 *
	 * @return void
	 */
	public function enqueue() {
		global $typenow;

		if (
			! in_array(
				$typenow,
				$this->autopaging_instance->post_types,
				true
			)
		) {
			return;
		}

		$asset_handle = 'automatically-paginate-posts-block-editor';

		$plugin_base_dir = dirname( dirname( __FILE__ ) );

		$asset_data = require $plugin_base_dir
			. '/assets/build/index.asset.php';

		wp_enqueue_script(
			$asset_handle,
			plugins_url( 'assets/build/index.js', dirname( __FILE__ ) ),
			$asset_data['dependencies'],
			$asset_data['version'],
			true
		);

		wp_localize_script(
			$asset_handle,
			'autopagingSettings',
			array(
				'metaKey' => $this->autopaging_instance->meta_key,
			)
		);

		wp_set_script_translations(
			$asset_handle,
			'automatically-paginate-posts',
			$plugin_base_dir . '/languages'
		);
	}
}
