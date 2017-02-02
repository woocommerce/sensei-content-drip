<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Content Drip ( scd ) Manual Drip functionality
 *
 * This class handles all of the functionality for the manual drip override functionality
 *
 * @package WordPress
 * @subpackage Sensei Content Drip
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct
 * - manual_drip_interface
 * - update_manual_drip_activity
 * - localize_data
 * - scd_manual_drip_admin_notice
 * - manipulate_drip_status
 * - get_manual_drip_status
 * - send_learner_lesson_manual_drip_status
 */

class Scd_Ext_Manual_Drip {
	/**
	 * Token variable referencing the global sense content drip token
	 *
	 * @access private
	 * @var    string
	 */
	private $_token;

	/**
	 * Constructor
	 *
	 * @param string $scd_token
	 */
	public function __construct( $scd_token = 'sensei_content_drip' ) {
		$this->_token = $scd_token;

		add_filter( 'scd_is_drip_active', array( $this, 'manipulate_drip_status' ), 1 ,2 );

		if ( is_admin() ) {
			// Add the interface
			add_action( 'sensei_learners_extra', array( $this, 'manual_drip_interface' ) );

			// Save the data
			add_action( 'admin_init', array( $this, 'update_manual_drip_activity' ) );

			// Load the script and localize admin data
			add_action( 'admin_enqueue_scripts', array( $this, 'localize_data' ), 100 , 1 );

			// Listen for incoming ajax requests
			add_action( 'wp_ajax_get_manual_drip_status', array( $this , 'send_learner_lesson_manual_drip_status' ) );
			add_action( 'wp_ajax_nopriv_get_manual_drip_status', array( $this , 'send_learner_lesson_manual_drip_status' ) );
			add_action( 'wp_ajax_send_test_email', array( $this , 'send_test_email' ) );
		}
	}

	/**
	 * Markup for the manual drip functionality
	 *
	 * @return void
	 */
	public function manual_drip_interface() {
		$course_id = isset( $_GET['course_id'] ) ? $_GET['course_id'] : 0 ;
		if ( empty( $course_id ) ) {
			return;
		}

		// Get al the users taking this course
		$course_users   = Sensei_Content_Drip()->utils->get_course_users( $course_id );
		$course_lessons = Sensei_Content_Drip()->lesson_admin->get_course_lessons( $course_id );
		?>
		<div class="postbox scd-learner-managment manual-content-drip">
				<h3><span><?php esc_html_e( 'Manual Content Drip', 'sensei-content-drip' ); ?></span></h3>
				<div class="inside">
					<form name="scd_manual_drip_learners_lesson" action="" method="post">
						<p>
							<?php esc_html_e( 'Use this to give a learner access to any lesson (or remove existing access), overriding the content drip schedule.', 'sensei-content-drip' ); ?>
						</p>
						<p>
							<select name="scd_select_learner" id="scd_select_learner">
								<option value=""><?php esc_html_e( 'Select learner', 'sensei-content-drip' ); ?></option>
								<?php
								// Add the users as option
								foreach ( $course_users as $user_id ) {
									echo '<option value="' . esc_attr( $user_id ) . '" >';

									// Get the users details
									$user         = get_user_by('id', $user_id );
									$first_name   = $user->first_name ;
									$last_name    = $user->last_name;
									$display_name = $user->display_name;

									echo esc_html( $first_name . ' ' . $last_name . ' ( ' . $display_name . ' ) ' );
									echo '</option>';
								}
								?>
							</select>
						</p>
						<p>
							<select name="scd_select_course_lesson" id="scd_select_course_lesson" class=''>
								<option value=""><?php esc_html_e( 'Select a Lesson', 'sensei-content-drip' ); ?></option>
								<?php
								// Add the users as option
								foreach ( $course_lessons as $lesson ) {
									echo '<option value="' . esc_attr( $lesson->ID ) . '">';

									// Get the lesson title
									echo esc_html( $lesson->post_title );
									echo '</option>';
								}
								?>
							</select>
							<img src="<?php echo esc_url( admin_url() . 'images/wpspin_light.gif' ); ?>" class="loading hidden" style="margin-left: 0.5em;" />
						</p>
						<p><?php submit_button( esc_html__( 'Give Access', 'sensei-content-drip' ), 'primary', 'scd_log_learner_lesson_manual_drip_submit', false, array() ); ?></p>
						<?php echo wp_nonce_field( 'scd_log_learner_lesson_manual_drip', 'scd_learner_lesson_manual_drip' ); ?>
					</form>
				</div>
		</div>
		<?php
	}

	/**
	 * Get the $_POST form data and update the users lesson manual drip status
	 *
	 * @return void
	 */
	public function update_manual_drip_activity() {
		global $woothemes_sensei;

		// Verify nonce field exist
		if ( ! isset( $_POST['scd_learner_lesson_manual_drip'] ) ) {
			return;
		}

		// Verify the nonce
		if ( ! wp_verify_nonce( $_POST['scd_learner_lesson_manual_drip'], 'scd_log_learner_lesson_manual_drip' ) ) {
			return;
		}

		// Verify incomming fields
		if ( ! isset( $_POST[ 'scd_select_learner' ] )
			|| empty( $_POST[ 'scd_select_learner' ] )
			|| ! isset( $_POST[ 'scd_select_course_lesson' ] )
			|| empty( $_POST[ 'scd_select_course_lesson' ] )
			|| ! isset( $_POST[ 'scd_log_learner_lesson_manual_drip_submit' ] ) ) {
			// Exit
			return;
		}

		// Get the $_POST values
		$user_id   = absint( $_POST[ 'scd_select_learner' ] );
		$lesson_id = absint( $_POST[ 'scd_select_course_lesson' ] );

		// Get the users details
		$user = get_user_by( 'id', $user_id );

		if ( 'WP_User' !== get_class( $user ) ) {
			return;
		}

		// Create the log argument
		$args = array(
			'post_id'    => $lesson_id,
			'username'   => $user->user_login,
			'user_email' => $user->user_email,
			'user_url'   => $user->user_url,
			'data'       => 'true',
			'type'       => 'scd_manual_drip', // FIELD SIZE 20
			'parent'     => 0,
			'user_id'    => $user->ID,
			'action'     => 'update'
		);

		if ( 'Give Access' === $_POST[ 'scd_log_learner_lesson_manual_drip_submit' ] ) {
			// Log the users activity on the lesson drip
			$activity_updated = Sensei_Utils::sensei_log_activity( $args );
		} else {
			// Log the users activity on the lesson drip
			$activity_updated  = Sensei_Utils::sensei_delete_activities( $args );
		}

		add_action( 'admin_notices', array( $this, 'scd_manual_drip_admin_notice' ) );
	}

	/**
	 * Localize the 'scdManualDrip' data for JS activity
	 *
	 * @return void
	 */
	public function localize_data() {
		// Setup the data to be localized
		$data =  array(
			'nonce' => wp_create_nonce( 'get-manual-drip-status' ),
		);

		wp_localize_script( $this->_token . '-admin-manual-drip-script', 'scdManualDrip', $data );
		wp_localize_script( $this->_token . '-lesson-admin-script', 'scdManualDrip', $data );
	}

	/**
	 * Show the success on update
	 *
	 * @return void
	 */
	public function scd_manual_drip_admin_notice() {
		?>
		<div class="updated">
			<p><?php esc_html_e( 'Manual Drip Status Saved', 'sensei-content-drip' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Possibly change the drip active status
	 *
	 * @param  string $hide_lesson_content
	 * @param  int $lesson_id
	 * @return string $hide_lesson_content
	 */
	public function manipulate_drip_status( $hide_lesson_content ,  $lesson_id ) {
		$current_user = wp_get_current_user();

		// Return the default value if this is not a valid users
		// or if the lesson has no drip set for this user
		if ( 'WP_User' !== get_class( $current_user ) || ! $hide_lesson_content ) {
			return $hide_lesson_content;
		}

		$user_id            = $current_user->ID;
		$manual_drip_active = $this->get_manual_drip_status( $user_id, $lesson_id );

		// If the manual drip is active this post should be dripped for this user
		if ( $manual_drip_active ) {
			$hide_lesson_content = false;
		} else {
			$hide_lesson_content = true;
		}

		return $hide_lesson_content;
	}

	/**
	 * get_manual_drip_status
	 *
	 * @since  1.0.0
	 * @param  int $user_id
	 * @param  int $lesson_id
	 * @return bool
	 */
	public function get_manual_drip_status( $user_id, $lesson_id  ){
		if ( empty( $user_id ) ) {
			return false;
		}

		// Get the lesson/course sensei activity for drip manual drip
		$args = array(
			'post_id' => intval( $lesson_id ),
			'user_id' => $user_id,
			'type'    => 'scd_manual_drip'
		);

		// Get the sensei activity, false asks to only return the comment count
		$activity = Sensei_Utils::sensei_check_for_activity( $args, false );

		// Set  the drip status value
		if ( ! empty( $activity ) && $activity > 0 ) {
			$drip_status = true;
		} else {
			$drip_status = false;
		}

		return $drip_status;
	}

	/**
	 * AJAX method for sending test e-mails
	 *
	 * @return void
	 */
	public function send_test_email() {
		// Incoming request security
		check_ajax_referer( 'get-manual-drip-status', 'nonce' );

		// Incoming request required data check
		if ( ! isset( $_POST[ 'userId' ] ) || ! isset( $_POST[ 'lessonId' ] ) ) {
			wp_send_json_error( array( 'notice' => 'The userID and lessonID are required' ) );
			die;
		}

		// Setup the new security nonce
		$new_nonce = wp_create_nonce( 'get-manual-drip-status' );

		// Check for a valid user
		$user_id = $_POST[ 'userId' ];
		$user    = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			// Create the error response array
			$response = array(
				'notice'   => 'There is no user that matches this userID ( ' . $user_id . ' )',
				'newNonce' => $new_nonce,
			);

			wp_send_json_error( $response );
			die;
		}

		// Check for a valid lesson
		$lesson_id = $_POST[ 'lessonId' ];
		$lesson    = get_post( $lesson_id );

		if ( is_null( $lesson ) || empty( $lesson ) ) {
			// Create the error response array
			$response = array(
				'notice'   => 'There is no lesson that matches this lessonId ( ' . $lesson_id . ' )',
				'newNonce' => $new_nonce,
			);

			wp_send_json_error( $response );
			die;
		}

		$drip_email = new Scd_Ext_Drip_Email();
		$drip_email->send_single_email_drip_notifications( $_POST[ 'userId' ], array( $_POST[ 'lessonId' ] ) );

		// Setup the response array and new nonce
		$response = array(
			'success' => true,
			'data'    => array(
				'notice' => 'Test mail sent',
				'newNonce' => $new_nonce,
			),
		);

		wp_send_json( $response );
		wp_die();
	}

	/**
	 * User lesson manual drip status json data for the incoming ajax request
	 *
	 * @return void
	 */
	public function send_learner_lesson_manual_drip_status() {
		// Incoming request security
		check_ajax_referer( 'get-manual-drip-status', 'nonce' );

		// Incoming request required data check
		if ( ! isset( $_POST['userId'] ) || ! isset( $_POST['lessonId'] ) ) {
			wp_send_json_error( array( 'notice' => 'The userID and lessonID required' ) );
			die;
		}

		// Setup the new security nonce
		$new_nonce = wp_create_nonce( 'get-manual-drip-status' );

		// Check for a valid user
		$user_id = absint( $_POST['userId'] );
		$user    = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			// Create the error response array
			$response = array(
				'notice'   => sprintf( esc_html__( 'The userID( %d ) is invalid, there is no user that matches this ID ', 'sensei-content-drip' ), $user_id ),
				'newNonce' => $new_nonce,
			);

			wp_send_json_error( $response );
			die;
		}

		// Check for a valid lesson
		$lesson_id = absint( $_POST['lessonId'] );
		$lesson    = get_post( $lesson_id );

		if ( is_null( $lesson ) || empty( $lesson ) ) {
			// Create the error response array
			$response = array(
				'notice'   => sprintf( esc_html__( 'The lessonId( %d ) is invalid, there is no lesson that matches this ID ', 'sensei-content-drip' ), $lesson_id ),
				'newNonce' => $new_nonce,
			);

			wp_send_json_error( $response );
			die;
		}

		// Get the manual drip activity
		$manual_drip_status = $this->get_manual_drip_status( $user_id, $lesson_id );

		// Setup the response array and new nonce
		$response = array(
			'success' => true,
			'data'    => array(
				'userId'           => $user_id,
				'lessonId'         => $lesson_id,
				'manualDripStatus' => $manual_drip_status,
				'newNonce'         => $new_nonce,
			),
		);

		wp_send_json( $response );
		return;
	}
}
