<?php

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Sensei_Content_Drip
 *
 * The main class for the content drip plugin. This class hooks the plugin into the required WordPress actions.
 */
class Sensei_Content_Drip {

	/**
	 * The single instance of Sensei_Content_Drip.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file, $version = '1.0.0' ) {
		global $woo_sensei_content_drip;

		// create a global instace for further reference to this main class
		$woo_sensei_content_drip =  $this;

		$this->_version = $version;
		$this->_token = 'sensei_content_drip';

		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Handle localisation
		$this->load_plugin_textdomain ();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// include classes
		if( $this->_load_class_file('settings') ) { $this->settings = new Scd_Ext_settings();  } 
		if( $this->_load_class_file('utilities') ) { $this->utils = new Sensei_Scd_Extension_Utils();  } 
		if( $this->_load_class_file('lesson-frontend') ) { $this->lesson_frontend = new Scd_ext_lesson_frontend();  } 
		if( $this->_load_class_file('lesson-admin') ) { $this->lesson_admin = new Scd_ext_lesson_admin();  } 
		if( $this->_load_class_file('drip-email') ) { $this->drip_email = new Scd_Ext_drip_email();  } 
		if( $this->_load_class_file('learner-management') ) { $this->learner_managment = new Scd_Ext_Learner_Management();  } 

	} // End __construct()

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		global $woothemes_sensei;

		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array( $woothemes_sensei->token . '-frontend' ), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_scripts () {
		global $woothemes_sensei;

		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		global $post;
		//load the lesson idit/new screen css
		if( ( 'post.php' === $hook || 'post-new.php' === $hook ) && ( !empty($post) && 'lesson' === $post->post_type) ) {
            wp_register_style($this->_token . '-admin-lesson', esc_url($this->assets_url) . 'css/admin-lesson.css', array(), $this->_version);
            wp_enqueue_style($this->_token . '-admin-lesson');
        }
	} // End admin_enqueue_styles()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		global $post;
		//load the lesson idit/new screen script
		if( ( 'post.php' === $hook || 'post-new.php' === $hook ) && ( !empty($post) && 'lesson' === $post->post_type) ){
	
			wp_register_script( $this->_token . '-lesson-admin-script', esc_url( $this->assets_url ). 'js/admin-lesson'. $this->script_suffix .'.js' , array( 'underscore','jquery', 'backbone' ), $this->_version , true);
			wp_enqueue_script( $this->_token . '-lesson-admin-script' );
		}
	} // End admin_enqueue_scripts()

	/**
	 * Load plugin localisation.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'sensei-content-drip' , false , dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation()

	/**
	 * Load plugin textdomain.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'sensei-content-drip';
        /**
         * Action filter  change plugin locale
         *
         * @param string
         */
	    $locale = apply_filters( 'plugin_locale' , get_locale() , $domain );

	    load_textdomain( $domain , WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain , FALSE , dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain

	/**
	 * Main Sensei_Content_Drip Instance
	 *
	 * Ensures only one instance of Sensei_Content_Drip is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Sensei_Content_Drip()
	 * @return Main Sensei_Content_Drip instance
	 */
	public static function instance ( $file, $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self( $file, $version );
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	}// end _log_version_number

	/**
	 * return the plugins asset_url
     *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function get_asset_url() {
		return $this->asset_url;
	}// end get_asset_url

	/**
	 * Load class and add them to the main class ass child objects sensei_content_drip->child 
	 *
	 * @access  protected
	 * @since   1.0.0
	 * @param   string $class
	 * @return  void
	 */
	private function _load_class_file( $class ) {

		if( '' == $class || empty( $class ) ){
			return false;
		}

		// build the full class file name and path
		$full_class_file_name = 'class-scd-ext-'.trim( $class ).'.php' ;
		$file_path = $this->dir . '/includes/' . $full_class_file_name;

		// check if the file exists 
		if( '' == $full_class_file_name || 
			empty( $full_class_file_name ) || 
			! file_exists( $file_path ) ){
			return false;
		} 

		// include the class file
		require_once( realpath ( $file_path ) );

		// succes indeed 
		return true;
	}// end _load_class_file
}// end class Sensei_Content_Drip