<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Automatically_Paginate_Posts
 */

$autopaging_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $autopaging_tests_dir ) {
	$autopaging_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $autopaging_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $autopaging_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $autopaging_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function autopaging_manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/automatically-paginate-posts.php';
}
tests_add_filter( 'muplugins_loaded', 'autopaging_manually_load_plugin' );

// Start up the WP testing environment.
require $autopaging_tests_dir . '/includes/bootstrap.php';
