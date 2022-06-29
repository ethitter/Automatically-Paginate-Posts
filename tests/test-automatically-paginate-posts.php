<?php
/**
 * Test main plugin class.
 *
 * @package Automatically_Paginate_Posts
 */

/**
 * @coversDefaultClass Automatically_Paginate_Posts
 */
class SampleTest extends WP_UnitTestCase {
	protected $_instance;

	public function set_up() {
		parent::set_up();

		$this->_instance = new Automatically_Paginate_Posts();
	}

	/**
	 * @covers ::__get()
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
	 * @covers ::filter_plugin_action_links()
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
}
