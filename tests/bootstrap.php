<?php

class Sensei_Content_Drip_Unit_Tests_Bootstrap {
	/** @var Sensei_Content_Drip_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/** @var string Sensei plugin directory */
	public $sensei_plugin_dir;

	/**
	 * Sensei_Content_Drip_Unit_Tests_Bootstrap constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions, WordPress.PHP.DevelopmentFunctions
		ini_set( 'display_errors', 'on' );
		error_reporting( E_ALL );

		// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions, WordPress.PHP.DevelopmentFunctions
		// Ensure server variable is set for WP email functions.
		// phpcs:disable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
			$_SERVER['SERVER_NAME'] = 'localhost';
		}

		// phpcs:enable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		$this->tests_dir                   = dirname( __FILE__ );
		$this->plugin_dir                  = dirname( $this->tests_dir );
		$this->wp_tests_dir                = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';
		$this->sensei_plugin_dir           = getenv( 'SENSEI_PLUGIN_DIR' ) ? getenv( 'SENSEI_PLUGIN_DIR' ) : '/tmp/sensei-master';
		$this->sensei_tests_framework_dir  = $this->sensei_plugin_dir . '/tests/framework';

		if (
			! file_exists( $this->wp_tests_dir . '/includes/functions.php' )
			|| ! file_exists( $this->wp_tests_dir . '/includes/bootstrap.php' )
		) {
			echo sprintf( 'WordPress testing library not found at %s', $this->wp_tests_dir ) . PHP_EOL;
			exit( 1 );
		}

		// load test function so tests_add_filter() is available
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// Load Sensei LMS and Content Drip.
		tests_add_filter( 'muplugins_loaded', [ $this, 'load_sensei' ] );
		tests_add_filter( 'muplugins_loaded', [ $this, 'load_plugin' ] );

		// Install Sensei LMS.
		tests_add_filter( 'setup_theme', [ $this, 'install_sensei' ] );


		// Load the WP testing environment.
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';
		require dirname( dirname( __FILE__ ) ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

		// Load the Sensei testing framework.
		require_once $this->sensei_tests_framework_dir . '/factories/class-sensei-factory.php';
		require_once $this->sensei_tests_framework_dir . '/factories/class-wp-unittest-factory-for-post-sensei.php';
		require_once $this->sensei_tests_framework_dir . '/trait-sensei-test-login-helpers.php';

		// Load this plugin's test framework.
		require_once $this->tests_dir . '/framework/drip-test-helpers.php';
		require_once $this->tests_dir . '/framework/class-time-machine.php';
	}

	/**
	 * Load Sensei LMS.
	 *
	 * @since 1.0.0
	 */
	public function load_sensei() {
		$sensei_file_search = [
			$this->sensei_plugin_dir . '/sensei-lms.php',
		];

		$sensei_found = false;

		foreach ( $sensei_file_search as $sensei_file ) {
			if ( file_exists( $sensei_file ) ) {
				require_once $sensei_file;
				$sensei_found = true;
				break;
			}
		}

		if ( ! $sensei_found ) {
			echo sprintf( 'Sensei LMS not found at any of these locations: %s', implode( '; ', $sensei_file_search ) ) . PHP_EOL;
			exit( 1 );
		}

		Sensei()->activate();
	}

	/**
	 * Loads the Content Drip plugin.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin() {
		define( 'SENSEI_CONTENT_DRIP_SKIP_DEPS_CHECK', true );
		require_once $this->plugin_dir . '/sensei-content-drip.php';
	}

	/**
	 * Checks and sets up Sensei.
	 *
	 * @since 1.0.0
	 */
	public function install_sensei() {
		if (
			! function_exists( 'Sensei' )
			|| version_compare( '2.0.0', Sensei()->version, '>' )
		) {
			echo 'Sensei 2 not found' . PHP_EOL;
			exit( 1 );
		}
	}

	/**
	 * Get the single class instance.
	 *
	 * @since 1.0.0
	 * @return Sensei_Content_Drip_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Sensei_Content_Drip_Unit_Tests_Bootstrap::instance();
