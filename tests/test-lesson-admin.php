<?php
/**
* Testing the Sensei Content Drip ( scd ) Email functionality class
* @class Scd_Ext_lesson_admin
* @file ../includes/class-scd-ext-drip-email.php
*/
class LessonAdminTest extends WP_UnitTestCase {
	function testClass() {
		$this->assertTrue( class_exists( 'Scd_Ext_Lesson_Admin' ), 'The plugin class file was not loaded' );
		$this->assertTrue( isset( Sensei_Content_Drip()->lesson_admin ), 'The plugin class was not intstantiated' );
	}

	function testGetAllDrippingLessons() {
		// Setup test lessons
		$lessons = array();

		$lessons[0] = $this->factory->post->create( array( 'post_type' => 'lesson' ) );
		add_post_meta( $lessons[0], '_sensei_content_drip_type', 'absolute' );

		$lessons[1] = $this->factory->post->create( array( 'post_type' => 'lesson' ) );
		add_post_meta( $lessons[1], '_sensei_content_drip_type', 'absolute' );

		$lessons[2] = $this->factory->post->create( array( 'post_type' => 'lesson' ) );
		add_post_meta( $lessons[2], '_sensei_content_drip_type', 'dynamic' );

		$lessons[3] = $this->factory->post->create( array( 'post_type' => 'lesson' ) );
		add_post_meta( $lessons[3], '_sensei_content_drip_type', 'dynamic' );

		$dripping_lessons = Scd_Ext_Lesson_Admin::get_all_dripping_lessons();

		$this->assertSame( $lessons ,$dripping_lessons  );
	}
}
