<?php
//security first
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Sensei_Content_Drip
 *
 * The main class for the content drip plugin. This class hooks the plugin into the required WordPress actions.
 *
 * @package WordPress
 * @subpackage Sensei Content Drip
 * @category Utilities
 * @author WooThemes
 * @since 1.0.0
 *
 * Table Of Contents:
 * - __construct
 * - enqueue_styles
 * - enqueue_scripts
 * - admin_enqueue_styles
 * - admin_enqueue_scripts
 * - load_localisation
 * - load_plugin_textdomain
 * - instance
 * - __clone
 * - __wakeup
 * - install
 * - _log_version_number
 * - get_asset_url
 * - initialize_classes
 * - get_date_format_string
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
	 * Suffix for Javascript files.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @param $file
	 * @param string $version
	 */
	public function __construct ( $file, $version = '1.0.1' ) {

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

		// Load and initialize classes
        add_action( 'init', array( $this, 'initialize_classes' ), 0 );

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
		// wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		//wp_enqueue_script( $this->_token . '-frontend' );
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
			// Jquey UI
			wp_register_style('scd-jquery-ui', esc_url($this->assets_url) . 'css/jquery-ui.css', array(), $this->_version);
			wp_enqueue_style('scd-jquery-ui');
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
			wp_register_script( $this->_token . '-lesson-admin-script', esc_url( $this->assets_url ). 'js/admin-lesson'. $this->script_suffix .'.js' , array( 'underscore','jquery', 'jquery-ui-datepicker' , 'backbone' ), $this->_version , true);
			wp_enqueue_script( $this->_token . '-lesson-admin-script' );
		}

        //load the learner management functionality script
        if( 'sensei_page_sensei_learners' === $hook &&  isset( $_GET['course_id'] ) && isset( $_GET['view'] ) && 'learners'=== $_GET['view']  ){
            wp_register_script( $this->_token . '-admin-manual-drip-script', esc_url( $this->assets_url ). 'js/admin-manual-drip'. $this->script_suffix .'.js' , array( 'underscore','jquery', 'backbone' ), $this->_version , true);
            wp_enqueue_script( $this->_token . '-admin-manual-drip-script' );
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
	public static function instance ( $file, $version = '1.0.1' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
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
	private function _log_version_number() {
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
     * Initialize classed need needed within this pluin
     *
     * @access  protected
     * @since   1.0.0
     * @param   string $class
     * @return  void
     */
    public function initialize_classes() {
        $classes = array('settings',
                        'utilities',
                        'access-control',
						'lesson-frontend',
                        'lesson-admin',
						'quiz-frontend',
                        'drip-email',
                        'manual-drip');

        foreach( $classes as $class_id ) {
            // build the full class file name and path
            $full_class_file_name = 'class-scd-ext-'.trim( $class_id ).'.php' ;

            $file_path = $this->dir . '/includes/' . $full_class_file_name;

            // check if the file exists
            if( '' == $full_class_file_name ||
                empty( $full_class_file_name ) ||
                ! file_exists( $file_path ) ){
                    continue;
            }

            // include the class file
            require_once( realpath ( $file_path ) );

        }// end for each

        // instantiate the classes
        $this->settings = new Scd_Ext_Settings();
        $this->utils = new Scd_Ext_Utils();
        $this->access_control = new Scd_Ext_Access_Control();
		$this->lesson_frontend = new Scd_Ext_Lesson_Frontend();
        $this->lesson_admin = new Scd_Ext_Lesson_Admin();
		$this->quiz_frontend = new Scd_Ext_Quiz_Frontend();
        $this->drip_email = new Scd_Ext_Drip_Email();
        $this->manual_drip = new Scd_Ext_Manual_Drip();

    }// end _initialize_classes

	/**
	 * get the date format and allow the user to filter it. This format applies for the whole
	 * content drip extension
	 *
	 * @since 1.0.0
	 * @return string $date_format
	 */
	public function get_date_format_string(){

		$date_format = 'l jS F Y';
		/**
		 * filter scd_drip_message_date_format
		 * @param string
		 * @deprecated since 1.0.2
		 */
		return apply_filters( 'scd_drip_message_date_format' , $date_format );

	}//end get_date_format

}// end class Sensei_Content_Drip