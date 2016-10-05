<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugins() {
	require plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'woothemes-sensei/woothemes-sensei.php';

	// Add sensei to list of active plugins
	update_option( 'active_plugins', array( 'woothemes-sensei/woothemes-sensei.php' ) );

	require dirname( __FILE__ ) . '/../sensei-content-drip.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugins' );

require $_tests_dir . '/includes/bootstrap.php';
