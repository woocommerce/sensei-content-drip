<?php

use Scd_Ext\Tests\Time_Machine;
use function Scd_Ext\Tests\set_absolute_drip_date;
use function Scd_Ext\Tests\set_dynamic_drip_date;


/**
 * Testing the Sensei Content Drip Access Control class.
 *
 * @class Scd_Ext_Access_Control
 */
class Scd_Ext_Access_Control_Tests extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Sensei post factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up before each test.
	 */
	public function setUp() {
		$this->factory = new Sensei_Factory();
		$this->originalUtils = Sensei_Content_Drip()->utils;

		Time_Machine::activate( true );
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown() {
		Time_Machine::deactivate();
	}

	/**
	 * Test getting a simple absolute date.
	 */
	public function testGetLessonDripDateAbsolute() {
		$lesson_id = $this->factory->lesson->create();
		$today = Sensei_Content_Drip()->utils->current_datetime();
		$last_week = $today->sub( new DateInterval( 'P7D' ) )->setTime( 0, 0, 0 );

		set_absolute_drip_date( $lesson_id, $last_week );

		$drip_date = Sensei_Content_Drip()->access_control->get_lesson_drip_date( $lesson_id );

		$this->assertEquals( $last_week->getTimestamp(), $drip_date->getTimestamp() );
	}

	/**
	 * Test getting a relative absolute date.
	 */
	public function testGetLessonDripDateRelative() {
		$this->login_as_admin();

		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		Sensei_Utils::user_start_course( get_current_user_id(), $course_id );

		$today = Sensei_Content_Drip()->utils->current_datetime();
		$next_week = $today->add( new DateInterval( 'P5D' ) )->setTime( 0, 0, 0 );

		set_dynamic_drip_date( $lesson_id, 'day', 5 );

		$drip_date = Sensei_Content_Drip()->access_control->get_lesson_drip_date( $lesson_id, get_current_user_id() );

		$this->assertEquals( $next_week->getTimestamp(), $drip_date->getTimestamp() );
	}

	/**
	 * Time based test cases.
	 *
	 * @return string[][]
	 */
	public function tzDataProviders() {
		return [
			'plus-utc-tz-same' => [
				'2020-01-01 03:44:00',
				'+0800',
			],
			'plus-utc-tz-cusp' => [
				'2020-01-01 23:59:59',
				'+0300',
			],
			'minus-utc-tz' => [
				'2020-01-01 00:00:01',
				'-1000',
			],
		];
	}

	/**
	 * Test getting a simple absolute date with timezone shift from UTC.
	 *
	 * @dataProvider tzDataProviders
	 *
	 * @param string $datetime Datetime string.
	 * @param string $timezone Timezone.
	 */
	public function testGetLessonDripDateAbsoluteTZShift( $datetime, $timezone ) {
		$new_now = new DateTimeImmutable( $datetime, new DateTimeZone( $timezone ) );
		Time_Machine::set_datetime( $new_now );
		Time_Machine::set_timezone( $new_now->getTimezone() );

		$lesson_id = $this->factory->lesson->create();
		$today = Sensei_Content_Drip()->utils->current_datetime();
		$last_week = $today->sub( new DateInterval( 'P7D' ) )->setTime( 0, 0, 0 );

		set_absolute_drip_date( $lesson_id, $last_week );

		$drip_date = Sensei_Content_Drip()->access_control->get_lesson_drip_date( $lesson_id );

		$this->assertEquals( $last_week->getTimestamp(), $drip_date->getTimestamp() );
	}

	/**
	 * Test getting a simple relative date with timezone shift from UTC. Uses the `start` date.
	 *
	 * @dataProvider tzDataProviders
	 *
	 * @param string $datetime Datetime string.
	 * @param string $timezone Timezone.
	 */
	public function testGetLessonDripDateRelativeTZShiftStartDate( $datetime, $timezone ) {
		$new_now = new DateTimeImmutable( $datetime, new DateTimeZone( $timezone ) );
		Time_Machine::set_datetime( $new_now );
		Time_Machine::set_timezone( $new_now->getTimezone() );

		$this->login_as_admin();

		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$comment_id = Sensei_Utils::user_start_course( get_current_user_id(), $course_id );
		// This will use PHP's timezone when outputting the date format.
		update_comment_meta( $comment_id, 'start', date( 'Y-m-d H:i:s', $new_now->getTimestamp() ) );

		$today = Sensei_Content_Drip()->utils->current_datetime();
		$next_week = $today->add( new DateInterval( 'P5D' ) )->setTime( 0, 0, 0 );

		set_dynamic_drip_date( $lesson_id, 'day', 5 );

		$drip_date = Sensei_Content_Drip()->access_control->get_lesson_drip_date( $lesson_id, get_current_user_id() );

		$this->assertEquals( $next_week->getTimestamp(), $drip_date->getTimestamp() );
	}

	/**
	 * Test getting a simple relative date with timezone shift from UTC. Uses the comment GMT date. This is for
	 * legacy situations where we weren't setting the `start` meta.
	 *
	 * @dataProvider tzDataProviders
	 *
	 * @param string $datetime Datetime string.
	 * @param string $timezone Timezone.
	 */
	public function testGetLessonDripDateRelativeTZShiftCommentDate( $datetime, $timezone ) {
		global $wpdb;

		$new_now = new DateTimeImmutable( $datetime, new DateTimeZone( $timezone ) );
		Time_Machine::set_datetime( $new_now );
		Time_Machine::set_timezone( $new_now->getTimezone() );

		$this->login_as_admin();

		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$comment_id = Sensei_Utils::user_start_course( get_current_user_id(), $course_id );
		delete_comment_meta( $comment_id, 'start' );

		$wpdb->update(
			$wpdb->comments,
			[
				'comment_date'     => $new_now->format( 'Y-m-d H:i:s' ),
				'comment_date_gmt' => $new_now->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
			],
			[
				'comment_ID' => $comment_id,
			]
		);

		// The comment is stored in memory. We need to clear it manually.
		wp_cache_flush();

		$today = Sensei_Content_Drip()->utils->current_datetime();
		$next_week = $today->add( new DateInterval( 'P2D' ) )->setTime( 0, 0, 0 );

		set_dynamic_drip_date( $lesson_id, 'day', 2 );

		$drip_date = Sensei_Content_Drip()->access_control->get_lesson_drip_date( $lesson_id, get_current_user_id() );

		$this->assertEquals( $next_week->getTimestamp(), $drip_date->getTimestamp() );
	}

	/**
	 * Test `\Scd_Ext_Access_Control::is_dynamic_drip_type_content_blocked` is handling timezones correctly for
	 * dynamic drips based off of course start date.
	 *
	 * @dataProvider tzDataProviders
	 *
	 * @param string $datetime Datetime string.
	 * @param string $timezone Timezone.
	 */
	public function testIsDynamicDripTypeContentBlockedTZShiftStartDate( $datetime, $timezone ) {
		$new_now = new DateTimeImmutable( $datetime, new DateTimeZone( $timezone ) );
		Time_Machine::set_datetime( $new_now );
		Time_Machine::set_timezone( $new_now->getTimezone() );

		$this->login_as_admin();

		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$comment_id = Sensei_Utils::user_start_course( get_current_user_id(), $course_id );
		// This will use PHP's timezone when outputting the date format.
		update_comment_meta( $comment_id, 'start', date( 'Y-m-d H:i:s', $new_now->getTimestamp() ) );

		$today = Sensei_Content_Drip()->utils->current_datetime();

		set_dynamic_drip_date( $lesson_id, 'day', 2 );

		// On the same day, they should not have access.
		$this->assertTrue( Sensei_Content_Drip()->access_control->is_dynamic_drip_type_content_blocked( $lesson_id ), 'Access should be blocked on the day the user starts the course' );

		// Go to the next day.
		$tomorrow = $today->add( new DateInterval( 'P1D' ) );
		Time_Machine::set_datetime( $tomorrow );
		$this->assertTrue( Sensei_Content_Drip()->access_control->is_dynamic_drip_type_content_blocked( $lesson_id ), 'Access should be blocked on the day after the user starts the course' );

		// Go to the next day.
		$tomorrow = $tomorrow->add( new DateInterval( 'P1D' ) );
		Time_Machine::set_datetime( $tomorrow );
		$this->assertFalse( Sensei_Content_Drip()->access_control->is_dynamic_drip_type_content_blocked( $lesson_id ), 'Access should NOT be blocked on two days after the user starts the course' );

		// Go to the next day to make sure it still isn't blocked.
		$tomorrow = $tomorrow->add( new DateInterval( 'P1D' ) );
		Time_Machine::set_datetime( $tomorrow );
		$this->assertFalse( Sensei_Content_Drip()->access_control->is_dynamic_drip_type_content_blocked( $lesson_id ), 'Access should NOT be blocked on three days after the user starts the course' );
	}

	/**
	 * Test `\Scd_Ext_Access_Control::is_absolute_drip_type_content_blocked` is handling timezones correctly for
	 * absolute drips.
	 *
	 * @dataProvider tzDataProviders
	 *
	 * @param string $datetime Datetime string.
	 * @param string $timezone Timezone.
	 */
	public function testIsAbsoluteDripTypeContentBlockedTZShift( $datetime, $timezone ) {
		$new_now = new DateTimeImmutable( $datetime, new DateTimeZone( $timezone ) );
		Time_Machine::set_datetime( $new_now );
		Time_Machine::set_timezone( $new_now->getTimezone() );

		$this->login_as_admin();

		$lesson_id = $this->factory->lesson->create();

		$today = Sensei_Content_Drip()->utils->current_datetime();

		set_absolute_drip_date( $lesson_id, $new_now->add( new DateInterval( 'P3D' ) ) );

		// On the same day, they should not have access.
		$this->assertTrue( Sensei_Content_Drip()->access_control->is_absolute_drip_type_content_blocked( $lesson_id ), 'Access should be blocked on current+1' );

		// Go to the next day.
		$tomorrow = $today->add( new DateInterval( 'P1D' ) );
		Time_Machine::set_datetime( $tomorrow );
		$this->assertTrue( Sensei_Content_Drip()->access_control->is_absolute_drip_type_content_blocked( $lesson_id ), 'Access should be blocked on current+2' );

		// Go to the next day.
		$tomorrow = $tomorrow->add( new DateInterval( 'P1D' ) );
		Time_Machine::set_datetime( $tomorrow );
		$this->assertTrue( Sensei_Content_Drip()->access_control->is_absolute_drip_type_content_blocked( $lesson_id ), 'Access should be blocked on current+3' );

		// Go to the next day.
		$tomorrow = $tomorrow->add( new DateInterval( 'P1D' ) );
		Time_Machine::set_datetime( $tomorrow );
		$this->assertFalse( Sensei_Content_Drip()->access_control->is_absolute_drip_type_content_blocked( $lesson_id ), 'Access should NOT be blocked on current+4' );

		// Go to the next day to make sure it still isn't blocked.
		$tomorrow = $tomorrow->add( new DateInterval( 'P1D' ) );
		Time_Machine::set_datetime( $tomorrow );
		$this->assertFalse( Sensei_Content_Drip()->access_control->is_absolute_drip_type_content_blocked( $lesson_id ), 'Access should NOT be blocked on current+5' );
	}
}
