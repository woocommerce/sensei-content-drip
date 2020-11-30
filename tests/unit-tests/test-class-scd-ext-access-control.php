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
	 * Test getting a simple absolute date with +8 timezone shift from UTC.
	 */
	public function testGetLessonDripDateAbsoluteTZShift() {
		$new_now = new DateTimeImmutable( '2020-01-01 03:44:00', new DateTimeZone( '+0800' ) );
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
	 * Test getting a simple relative date with +8 timezone shift from UTC.
	 */
	public function testGetLessonDripDateRelativeTZShift() {
		$new_now = new DateTimeImmutable( '2020-01-01 15:59:00', new DateTimeZone( '+0800' ) );
		Time_Machine::set_datetime( $new_now );
		Time_Machine::set_timezone( $new_now->getTimezone() );

		$this->login_as_admin();

		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$comment_id = Sensei_Utils::user_start_course( get_current_user_id(), $course_id );
		update_comment_meta( $comment_id, 'start', date( 'Y-m-d H:i:s', $new_now->getTimestamp() ) );

		$today = Sensei_Content_Drip()->utils->current_datetime();
		$next_week = $today->add( new DateInterval( 'P5D' ) )->setTime( 0, 0, 0 );

		set_dynamic_drip_date( $lesson_id, 'day', 5 );

		$drip_date = Sensei_Content_Drip()->access_control->get_lesson_drip_date( $lesson_id, get_current_user_id() );

		$this->assertEquals( $next_week->getTimestamp(), $drip_date->getTimestamp() );
	}
}
