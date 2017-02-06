<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Content Drip ( scd ) Extension lesson admin class
 *
 * This class controls all admin functionality related to sensei lessons
 *
 * @package WordPress
 * @subpackage Sensei Content Drip
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 * - __construct
 * - add_lesson_content_drip_meta_box
 * - add_column_heading
 * - add_column_data
 * - content_drip_lesson_meta_content
 * - get_course_lessons
 * - save_course_drip_meta_box_data
 * - lesson_admin_notices
 * - get_meta_field_keys
 * - save_lesson_drip_data
 * - get_lesson_drip_data
 * - delete_lesson_drip_data
 * - get_all_dripping_lessons
 */

class Scd_Ext_Lesson_Admin {
	/**
	 * The token.
	 * @var    string
	 * @access private
	 * @since  1.0.0
	 */
	private $_token;
	const DATE_FORMAT = 'Y-m-d';

	/**
	 * Constructor function
	 */
	public function __construct() {
		// Set the plugin token for this class
		$this->_token = 'sensei_content_drip';

		// Add view all lessons columns
		add_filter( 'manage_edit-lesson_columns', array( $this, 'add_column_heading' ), 20, 1 );
		add_action( 'manage_posts_custom_column', array( $this, 'add_column_data' ), 20, 2 );

		// Hook int all post of type lesson to determin if they are
		add_action( 'add_meta_boxes', array( $this, 'add_lesson_content_drip_meta_box' ) );

		// Save the meta box
		add_action( 'save_post', array( $this, 'save_course_drip_meta_box_data' ) );

		// Admin notices
		add_action( 'admin_notices', array( $this, 'lesson_admin_notices' ) , 80 );

	}

	/**
	 * Hooking the meta box content into the edit lesson screen
	 *
	 * @since  1.0.0
	 * @return void
	 * @uses   the_posts()
	 */
	public function add_lesson_content_drip_meta_box() {
		add_meta_box(
			'content-drip-lesson',
			esc_html__( 'Sensei Content Drip', 'sensei-content-drip' ),
			array( $this, 'content_drip_lesson_meta_content' ),
			'lesson',
			'normal',
			'default',
			null
		);
	}

	/**
	 * Add a new column to the vew all lessons admin screen
	 *
	 * @since  1.0.0
	 * @param  array $columns
	 * @return array $columns
	 */
	public function add_column_heading( $columns ) {
		$columns['scd_drip_schedule'] = esc_html_x( 'Drip Schedule', 'column name', 'sensei-content-drip' );
		return $columns;
	}

	/**
	 * Attempts to retrieve the date in localized format (if using new format), otherwise return plain format
	 *
	 * @param  string $lesson_id
	 * @return DateTime|string
	 */
	private function date_or_datestring_from_lesson( $lesson_id ) {
		$lesson_set_date = get_post_meta( $lesson_id, '_sensei_content_drip_details_date', true );

		if ( ctype_digit( $lesson_set_date ) ) {
			// we are using new data in db, format accordingly
			$lesson_set_date = date_i18n( self::DATE_FORMAT, $lesson_set_date );
		}

		return $lesson_set_date;
	}

	/**
	 * Add data for our drip schedule custom column
	 *
	 * @since  1.0.0
	 * @param  string $column_name
	 * @param  int $id
	 * @return void
	 */
	public function add_column_data( $column_key, $lesson_id ) {
		// Exit early if this is not the column we want
		if ( 'scd_drip_schedule' !== $column_key ) {
			return;
		}

		// Get the lesson drip type
		$drip_type = Sensei_Content_Drip()->lesson_frontend->get_lesson_drip_type( $lesson_id );

		// Generate the messages
		if ( 'none' === $drip_type ) {
			echo esc_html__( 'Immediately', 'sensei-content-drip' );
		} else if ( 'absolute' === $drip_type ) {
			$lesson_set_date = $this->date_or_datestring_from_lesson( $lesson_id );
			printf( esc_html__( 'On %s', 'sensei-content-drip' ), $lesson_set_date );
		} else if ( 'dynamic' === $drip_type ) {
			$unit_type   = get_post_meta( $lesson_id , '_sensei_content_drip_details_date_unit_type', true );
			$unit_amount = get_post_meta( $lesson_id , '_sensei_content_drip_details_date_unit_amount', true );

			// Setup the time period strings
			$time_period = $unit_amount . ' ' . $unit_type;

			// Append an s to the unit if it is more than 1
			if ( $unit_amount > 1 ) {
				$time_period .= 's';
			}

			// Assemble and output
			printf( esc_html__( 'After %s', 'sensei-content-drip' ), $time_period );
		}
	}


	/**
	 * Display the content inside the meta box
	 *
	 * @since  1.0.0
	 * @return array $posts
	 * @uses   the_posts()
	*/
	public function content_drip_lesson_meta_content() {
		global $post;
		global $current_user;

		// Setup the forms value variable to be empty
		// this is to avoid php notices
		$selected_drip_type              = '';
		$absolute_date_value             = '';
		$selected_dynamic_time_unit_type = '';
		$dynamic_unit_amount             = '';

		// Get the lesson drip meta data
		$lesson_drip_data = $this->get_lesson_drip_data( $post->ID );

		// Get the lessons meta data
		$lesson_pre_requisite  = get_post_meta( $post->ID , '_lesson_prerequisite', true );
		$current_lesson_course = get_post_meta( $post->ID , '_lesson_course', true );

		// Show nothing if no course is selected
		if ( empty( $current_lesson_course ) ) {
			echo '<p>' . esc_html__( 'In order to use the content drip settings, please select a course for this lesson.', 'sensei-content-drip' ) . '</p>';

			// Exit without displaying the rest of the settings
			return;
		}

		// Set the selected drip type according to the meta data for this post
		$selected_drip_type = isset( $lesson_drip_data['_sensei_content_drip_type'] ) ? $lesson_drip_data['_sensei_content_drip_type'] :  'none' ;

		// Setup the hidden classes and assisgn the needed data
		if ( 'absolute' === $selected_drip_type ) {
			$absolute_hidden_class = '';
			$dymaic_hidden_class   = 'hidden';

			// Get the absolute date stored field value
			$absolute_date_value = $this->date_or_datestring_from_lesson( $post->ID );
		} else if ( 'dynamic' === $selected_drip_type ) {
			$absolute_hidden_class = 'hidden';
			$dymaic_hidden_class   = '';

			// Get the data array
			$selected_dynamic_time_unit_type = $lesson_drip_data['_sensei_content_drip_details_date_unit_type'];
			$dynamic_unit_amount             = $lesson_drip_data['_sensei_content_drip_details_date_unit_amount'];
		} else {
			$absolute_hidden_class = 'hidden';
			$dymaic_hidden_class   = 'hidden';
		}

		// Nonce field
		wp_nonce_field( -1, 'woo_' . $this->_token . '_noonce');
		?>
		<p><?php esc_html_e( 'When should this lesson become available?', 'sensei-content-drip' ); ?></p>
		<p><select name='sdc-lesson-drip-type' class="sdc-lesson-drip-type">
			<option <?php selected( 'none', $selected_drip_type ); ?> value="none" class="none"><?php esc_html_e( 'As soon as the course is started', 'sensei-content-drip' ); ?></option>
			<option <?php selected( 'absolute', $selected_drip_type ); ?> value="absolute" class="absolute"><?php esc_html_e( 'On a specific date', 'sensei-content-drip' ); ?></option>
			<?php
				// Does this lesson have a  pre-requisites lesson ?
				$has_pre_requisite = empty( $lesson_pre_requisite ) ? 'false'  : 'true' ;
			?>
			<option data-has-pre="<?php esc_attr_e( $has_pre_requisite ); ?>" <?php selected( 'dynamic', $selected_drip_type ); ?> value="dynamic"  class="dynamic"><?php esc_html_e( 'A specific interval after the course start date', 'sensei-content-drip' ); ?></option>
		</select></p>

		<p><div class="dripTypeOptions absolute <?php echo esc_attr( $absolute_hidden_class ); ?> ">
			<p><span class='description'><?php printf( esc_html__( 'Select the date on which this lesson should become available (accepted date format is %s)', 'sensei-content-drip' ), self::DATE_FORMAT ); ?></span></p>
			<input type="text" id="scd-lesson-datepicker" name="absolute[datepicker]" value="<?php echo esc_attr( $absolute_date_value ); ?>" class="absolute-datepicker" />
		</div></p>
		<p>
			<div class="dripTypeOptions dynamic <?php echo esc_attr( $dymaic_hidden_class ); ?>">
			<?php if ( empty( $current_lesson_course ) ) : ?>
				<p>
					<?php esc_html_e( 'Please select a course for this lesson in order to use this drip type.', 'sensei-content-drip' ); ?>
				</p>
			<?php else : ?>
				<div id="dynamic-dripping-1" class='dynamic-dripping'>
					<input type='number' name='dynamic-unit-amount[1]' class='unit-amount' value="<?php echo esc_attr( $dynamic_unit_amount ); ?>" />

					<select name='dynamic-time-unit-type[1]' class="dynamic-time-unit">
						<option <?php selected( 'day', $selected_dynamic_time_unit_type ); ?> value="day"><?php esc_html_e( 'Day(s)', 'sensei-content-drip' ); ?></option>
						<option <?php selected( 'week', $selected_dynamic_time_unit_type ); ?> value="week"><?php esc_html_e( 'Week(s)', 'sensei-content-drip' ); ?></option>
						<option <?php selected( 'month', $selected_dynamic_time_unit_type ); ?> value="month"><?php esc_html_e( 'Month(s)', 'sensei-content-drip' ); ?></option>
					</select>
				</div>
			<?php endif; ?>
		</div><!-- end dripTypeOptions -->
		<?php $send_test_email = sprintf( __('Send Test Email to %s', 'sensei-content-drip' ), $current_user->user_email ); ?>
		<a title="<?php echo esc_attr( $send_test_email ); ?>" href="#send-test-email" class="send_test_email button button-primary button-highlighted"><?php echo esc_html( $send_test_email ); ?></a>
		</p>
		<?php
	}

	/**
	 * Get the course lessons
	 *
	 * @access public
	 * @param  int $course_id (default: 0)
	 * @param  string $exclude
	 * @return array WP_Post
	 */
	public function get_course_lessons( $course_id = 0, $exclude = array() ) {
		$args = array(
			'post_type'          => 'lesson',
			'numberposts'        => -1,
			'meta_key'           => '_order_' . absint( $course_id ),
			'orderby'            => 'meta_value_num date',
			'order'              => 'ASC',
			'exclude'            => (array) $exclude,
			'meta_query'         => array(
				array(
					'key'   => '_lesson_course',
					'value' => absint( $course_id ),
				),
			),
			'post_status'        => 'public',
			'suppress_filters'   => 0
		);

		$lessons = get_posts( $args );

		return $lessons;
	}

	/**
	 * Listens to the save_post hook and saves the data accordingly
	 *
	 * @since  1.0.0
	 * @param  string $post_id
	 * @return string $post_id
	 */
	public function save_course_drip_meta_box_data( $post_id ) {
		global $post, $messages;

		// Verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Verify the nonce before proceeding
		if ( get_post_type() !== 'lesson'
			 || ! isset( $_POST[ 'woo_' . $this->_token . '_noonce' ] )
			 || ! wp_verify_nonce( $_POST[ 'woo_' . $this->_token . '_noonce' ] )
			 || ! isset( $_POST['sdc-lesson-drip-type'] ) ) {

			return $post_id;
		}

		// Retrieve the existing data
		$old_lesson_content_drip_data = $this->get_lesson_drip_data( $post_id );

		// New data holding array
		$new_data = array();

		// If none is selected and the previous data was also set to none return
		if ( 'none' === esc_html( $_POST['sdc-lesson-drip-type'] ) ) {
			// New data should be that same as default
			$new_data  = array( '_sensei_content_drip_type' => 'none' );
		} else if ( 'absolute' === esc_html( $_POST['sdc-lesson-drip-type'] ) ) {
			// Convert selected date to a unix time stamp
			// Incoming Format:  yyyy/mm/dd
			$date_string = esc_html( $_POST['absolute']['datepicker'] );

			if ( empty( $date_string ) ) {
				// Create the error message and add it to the database
				$message = esc_html__( 'Please choose a date under the  "Absolute" select box.', 'sensei-content-drip' );
				update_option( '_sensei_content_drip_lesson_notice' , array( 'error' => $message ) );

				// Set the current user selection
				update_post_meta( $post_id ,'_sensei_content_drip_type', 'none' );

				return $post_id;
			}


			// we are always expecting a specific time format for this field in the form of

			$date = DateTime::createFromFormat( self::DATE_FORMAT, $date_string );
			if (false === $date) {
				// possibly legacy, try to match the format from wp settings
				$date_format = get_option( 'date_format' );
				$date = DateTime::createFromFormat( $date_format, $date_string );
				if (false === $date) {
					// at this point we can't do somthing so we
					// need to prompt the user to reselect a date from the
					// datepicker. The old format will still work in the frontend
					$message = sprintf( esc_html__( 'The date format you selected cannot be parsed (we expect dates to be formatted like "%s")', 'sensei-content-drip' ), self::DATE_FORMAT );
					update_option( '_sensei_content_drip_lesson_notice' , array( 'error' => $message ) );
					return $post_id;
				}
			}
			$date_string = $date->getTimestamp();

			// Set the meta data to be saves later
			// Set the mets data to ready to pass it onto saving
			$new_data =  array(
				'_sensei_content_drip_type'         => 'absolute',
				'_sensei_content_drip_details_date' => $date_string,
			);

		} else if ( 'dynamic' === esc_html( $_POST['sdc-lesson-drip-type'] ) ) {
			// Get the posted data valudes
			$date_unit_amount = absint( $_POST['dynamic-unit-amount']['1'] );      // number of units
			$date_unit_type   = esc_html( $_POST['dynamic-time-unit-type']['1'] ); // unit type eg: months, weeks, days

			// Input validation
			$dynamic_save_error = false;
			if ( empty( $date_unit_amount ) || empty( $date_unit_type ) ) {
				$save_error_notices = array( 'error' => esc_html__( 'Please select the correct units for your chosen option "After previous lesson" .', 'sensei-content-drip' ) );
				$dynamic_save_error = true;
			} else if ( ! is_numeric( $date_unit_amount ) ) {
				$save_error_notices = array( 'error' => esc_html__( 'Please enter a numberic unit number for your chosen option "After previous lesson" .', 'sensei-content-drip' ) );
				$dynamic_save_error = true;
			}

			// Input error handling
			if ( true === $dynamic_save_error ) {
				update_option( '_sensei_content_drip_lesson_notice' , $save_error_notices );

				// Set the current user selection
				update_post_meta( $post_id ,'_sensei_content_drip_type', 'none' );

				// Exit with no further actions
				return $post_id;
			}

			// Set the mets data to ready to pass it onto saving
			$new_data =  array(
				'_sensei_content_drip_type'                     => 'dynamic',
				'_sensei_content_drip_details_date_unit_type'   => $date_unit_type,
				'_sensei_content_drip_details_date_unit_amount' => $date_unit_amount,
			);
		}

		// Update the meta data
		$this->save_lesson_drip_data( $post_id , $new_data  );

		return $post_id;
	}

	/**
	 * Lesson_admin_notices
	 * edit / new messages, loop through the messages save in the options table
	 * and display theme here
	 *
	 * @since  1.0.0
	 * @return array $posts
	 * @uses   the_posts()
	 */
	public function lesson_admin_notices() {
		// Retrieve the notice array
		$notice = get_option( '_sensei_content_drip_lesson_notice' );

		// If there are not notices to display exit
		if ( empty( $notice ) ) {
			return;
		}

		// Print all notices
		foreach ( $notice as $type => $message ) {
			$message =  $message . ' ' . esc_html__( 'The content drip type was reset to "none".', 'sensei-content-drip' );
			echo '<div class="' . esc_attr( $type ) . ' fade"><p>';
			printf( esc_html_x( 'Sensei Content Drip %s: %s', 'type and message', 'sensei-content-drip' ), $type, $message );
			echo '</p></div>';
		}

		// Clear all notices
		delete_option( '_sensei_content_drip_lesson_notice' );
	}

	/**
	 * Maintaining the acceptable list of meta data field keys for the lesson drip data.
	 *
	 * @return array
	 */
	public function get_meta_field_keys() {
		// Create an array of available keys that should be deleted
		$meta_fields_keys = array(
			'_sensei_content_drip_type',
			'_sensei_content_drip_details_date',
			'_sensei_content_drip_details_date_unit_type',
			'_sensei_content_drip_details_date_unit_amount',
		);

		return $meta_fields_keys;
	}

	/**
	 * Translates and array of key values into the respective post meta data key values
	 *
	 * @since  1.0.0
	 * @param  int $post_id
	 * @param  array $drip_form_data
	 * @return bool
	 */
	public function save_lesson_drip_data( $post_id, $drip_form_data ) {
		if ( empty( $post_id ) || empty( $drip_form_data ) ) {
			return false;
		}

		// Remove all existing sensei lesson drip data from the current lesson
		$this->delete_lesson_drip_data( $post_id );

		// Save each key respectively
		foreach ( $drip_form_data as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		return true;
	}

	/**
	 * Translates and array of key values into the respective post meta data key values
	 *
	 * @since  1.0.0
	 * @param  string $post_id
	 * @return array
	 */
	public function get_lesson_drip_data( $post_id ) {
		// Exit if and empty post id was sent through
		if ( empty( $post_id ) ) {
			return false;
		}

		// Get an array of available keys that should be deleted
		$meta_fields = $this->get_meta_field_keys();

		// Empty array that will store the return values
		$lesson_drip_data = array();

		foreach ( $meta_fields as $field_key ) {
			$value = get_post_meta( $post_id, $field_key, true );

			// Assign the key if a value exists
			if ( ! empty( $value ) ) {
				$lesson_drip_data[ $field_key ] = esc_html( $value );
			}
		}

		return $lesson_drip_data;
	}

	/**
	 * Cleans out the lessons existing drip meta data to prepare for saving
	 *
	 * @param  int $post_id
	 * @since  1.0.0
	 * @return void
	 */
	public function delete_lesson_drip_data( $post_id ) {
		if ( empty( $post_id ) ) {
			return false;
		}

		// Create an array of available keys that should be deleted
		$meta_fields = $this->get_meta_field_keys();

		foreach ( $meta_fields as $field_key ) {
			delete_post_meta( $post_id, $field_key );
		}
	}

	/**
	 * The function returns an array of lesson_ids.
	 * All those with drip type set to dynamic or absolute
	 *
	 * @static
	 * @return array Array containing lesson ids
	 */
	public static function get_all_dripping_lessons() {
		$lessons = array();

		// Determine the lesson query args
		$lesson_query_args = array(
			'post_type'      => 'lesson',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'   => '_sensei_content_drip_type',
					'value' => 'absolute',
				),
				array(
					'key'   => '_sensei_content_drip_type',
					'value' => 'dynamic',
				),
			),
		);

		// Get the lesson matching the query args
		$wp_lesson_objects = get_posts( $lesson_query_args );

		// Create the lessons id array
		if ( ! empty( $wp_lesson_objects ) ) {
			foreach ( $wp_lesson_objects as $lesson_object ) {
				$lessons[] = absint( $lesson_object->ID );
			}
		}

		return $lessons;
	}
}
