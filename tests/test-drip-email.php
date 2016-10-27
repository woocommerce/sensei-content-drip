<?php
/**
 * Testing the Sensei Content Drip ( scd ) Email functionality class
 * @class Scd_Ext_Lesson_Admin
 * @file ../includes/class-scd-ext-lesson-admin.php
 */
class DripEmailTest extends WP_UnitTestCase {
	public function testClassSetup() {
		$this->assertTrue( class_exists( 'Scd_Ext_drip_email' ), 'The plugin class file was not loaded' );
		$this->assertTrue( isset( Sensei_Content_Drip()->drip_email ), 'The plugin class was not intstantiated' );
	}

	/**
	 * Test Scd_Ext_Lesson_Admin::order_lesson_items
	 */
	public function testOrderCourseLessonItems() {
		// Setup courses
		$course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );

		// Setup the order we want the lesson id's to be in
		$course_lessons_order = '2,1,3,7,5,6';
		update_post_meta( $course_id, '_lesson_order', $course_lessons_order );

		// Test un ordered lesson items array
		$lesson_items = array(
			'3' => 'Sample item 1',
			'2' => 'Sample item 1',
			'1' => 'Sample item 3',
		);

		// Setup lessons for course 2
		$ordered_lesson_items = Sensei_Content_Drip()->drip_email->order_course_lesson_items( $lesson_items, $course_lessons_order );

		$this->assertEquals( array( '2', '1', '3' ) , array_keys( $ordered_lesson_items ) );
	}
}
