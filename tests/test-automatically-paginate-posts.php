<?php
/**
 * Test main plugin class.
 *
 * @package Automatically_Paginate_Posts
 */

/**
 * Class Test_Automatically_Paginate_Posts.
 *
 * @coversDefaultClass Automatically_Paginate_Posts
 */
class Test_Automatically_Paginate_Posts extends WP_UnitTestCase {
	/**
	 * Plugin instance.
	 *
	 * @var Automatically_Paginate_Posts
	 */
	protected $_instance;

	/**
	 * Prepare tests.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->_instance = new Automatically_Paginate_Posts();
		$this->_instance->action_init();
	}

	/**
	 * Test magic getter.
	 *
	 * @covers ::__get()
	 * @returns void
	 */
	public function test___get() {
		$this->assertEquals(
			'_disable_autopaging',
			$this->_instance->meta_key,
			'Failed to assert meta key matched expected value.'
		);

		// TODO: consider testing `post_types` along with a filter.

		$this->assertNull(
			$this->_instance->unit_test,
			'Failed to assert that unsupported key returns null.'
		);
	}

	/**
	 * Test modifications to plugin action links.
	 *
	 * @covers ::filter_plugin_action_links()
	 * @returns void
	 */
	public function test_filter_plugin_action_links() {
		$this->assertEmpty(
			$this->_instance->filter_plugin_action_links(
				array(),
				'unit-test.php'
			),
			'Failed to assert that no change is made for other plugins..'
		);

		$this->assertArrayHasKey(
			'settings',
			$this->_instance->filter_plugin_action_links(
				array(),
				'automatically-paginate-posts/automatically-paginate-posts.php'
			),
			'Failed to assert that settings link is added for this plugin.'
		);
	}

	/**
	 * Test `the_post` filtering when in admin.
	 *
	 * @covers ::filter_the_posts()
	 */
	public function test_filter_the_posts_admin() {
		$old_screen = $GLOBALS['current_screen'];

		$GLOBALS['current_screen'] = new Test_Autopaging_Admin();

		$test_posts = array(
			'unit-test',
		);

		$this->assertEquals(
			$test_posts,
			$this->_instance->filter_the_posts( $test_posts ),
			'Failed to assert that posts are not modified in admin.'
		);

		$GLOBALS['current_screen'] = $old_screen;
	}

	/**
	 * Test modifications to various posts.
	 *
	 * @covers ::filter_the_posts()
	 * @dataProvider data_provider_filter_the_posts
	 *
	 * @param string $expected Expected post content.
	 * @param array  $input    Test arguments.
	 */
	public function test_filter_the_posts( $expected, $input ) {
		$post = $this->factory->post->create_and_get( $input['post_args'] );

		// Bug in WPCS causes errors when `use` is used.
		// phpcs:disable WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceAfterStructureOpen, WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceBeforeOpenParenthesis
		add_filter(
			'pre_option_pages',
			static function() use( $input ) {
				return $input['type'];
			}
		);
		add_filter(
			'autopaging_num_pages',
			static function() use( $input ) {
				return $input['num_pages'];
			}
		);
		add_filter(
			'autopaging_num_words',
			static function() use( $input ) {
				return $input['num_words'];
			}
		);
		// phpcs:enable WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceAfterStructureOpen, WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceBeforeOpenParenthesis

		$this->assertEquals(
			$expected,
			$this->_instance->filter_the_posts( array( $post ) )[0]->post_content
		);
	}

	/**
	 * Data provide to test post filtering.
	 *
	 * @return array
	 */
	public function data_provider_filter_the_posts() {
		return array(
			'Unsupported type'        => array(
				"I am a page.\r\n\r\nI should not be paginated.",
				array(
					'post_args' => array(
						'post_type'    => 'page',
						'post_content' => "I am a page.\r\n\r\nI should not be paginated.",
					),
					'type'      => 'pages',
					'num_pages' => 2,
					'num_words' => 2,
				),
			),
			'Already paginated'       => array(
				"1\r\n\r\n<!--nextpage-->\r\n\r\n2\r\n\r\n3",
				array(
					'post_args' => array(
						'post_type'    => 'post',
						'post_content' => "1\r\n\r\n<!--nextpage-->\r\n\r\n2\r\n\r\n3",
					),
					'type'      => 'pages',
					'num_pages' => 2,
					'num_words' => 2,
				),
			),
			'Classic post, two pages, split to pages' => array(
				"1\r\n\r\n2<!--nextpage-->\r\n\r\n3",
				array(
					'post_args' => array(
						'post_type'    => 'post',
						'post_content' => "1\r\n\r\n2\r\n\r\n3",
					),
					'type'      => 'pages',
					'num_pages' => 2,
					'num_words' => 2,
				),
			),
			'Classic post, three pages, split to pages' => array(
				"1<!--nextpage-->\r\n\r\n2<!--nextpage-->\r\n\r\n3",
				array(
					'post_args' => array(
						'post_type'    => 'post',
						'post_content' => "1\r\n\r\n2\r\n\r\n3",
					),
					'type'      => 'pages',
					'num_pages' => 3,
					'num_words' => 2,
				),
			),
			'Classic post, one page, split on words' => array(
				"1\r\n\r\n2\r\n\r\n3\r\n\r\n4",
				array(
					'post_args' => array(
						'post_type'    => 'post',
						'post_content' => "1\r\n\r\n2\r\n\r\n3\r\n\r\n4",
					),
					'type'      => 'words',
					'num_pages' => 2,
					'num_words' => 5,
				),
			),
			'Classic post, two pages, split on words' => array(
				"1\r\n\r\n2<!--nextpage-->\r\n\r\n3\r\n\r\n4",
				array(
					'post_args' => array(
						'post_type'    => 'post',
						'post_content' => "1\r\n\r\n2\r\n\r\n3\r\n\r\n4",
					),
					'type'      => 'words',
					'num_pages' => 2,
					'num_words' => 2,
				),
			),
			'Classic post, three pages, split on words' => array(
				"1<!--nextpage-->\r\n\r\n2<!--nextpage-->\r\n\r\n3<!--nextpage-->\r\n\r\n4",
				array(
					'post_args' => array(
						'post_type'    => 'post',
						'post_content' => "1\r\n\r\n2\r\n\r\n3\r\n\r\n4",
					),
					'type'      => 'words',
					'num_pages' => 2,
					'num_words' => 1,
				),
			),
		);
	}
}

/**
 * Test class for admin-related restrictions.
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
class Test_Autopaging_Admin {
	/**
	 * Mock being in wp-admin.
	 *
	 * @return bool
	 */
	public function in_admin() {
		return true;
	}
}
