<?php
/**
* Testing the Sensei Content Drip ( scd ) Email functionality class
* @class Scd_Ext_Utils
* @file ../includes/class-scd-ext-drip-email.php
*/
class LessonUtilitiesTest extends WP_UnitTestCase {
	function testClass() {
		$this->assertTrue( class_exists( 'Scd_Ext_Utils' ), 'The plugin class file was not loaded' );
		$this->assertTrue( isset( Sensei_Content_Drip()->utils ), 'The plugin class was not intstantiated' );
	}

	function testGetDrippingLessonsByType(){
		// Setup test lessons
		$absolute_lessons = array();
		$dynamic_lessons  = array();

		$absolute_lessons[0] = $this->factory->post->create( array( 'post_type' => 'lesson' ) );
		add_post_meta( $absolute_lessons[0], '_sensei_content_drip_type', 'absolute' );

		$absolute_lessons[1] = $this->factory->post->create( array( 'post_type' => 'lesson' ) );
		add_post_meta( $absolute_lessons[1], '_sensei_content_drip_type', 'absolute' );

		$dynamic_lessons[0] = $this->factory->post->create( array( 'post_type' => 'lesson' ) );
		add_post_meta( $dynamic_lessons[0], '_sensei_content_drip_type', 'dynamic' );

		$dynamic_lessons[1] = $this->factory->post->create( array( 'post_type' => 'lesson' ) );
		add_post_meta( $dynamic_lessons[1], '_sensei_content_drip_type', 'dynamic' );

		$absolute_dripping_lessons = Sensei_Content_Drip()->utils->get_dripping_lessons_by_type( 'absolute' );
		$dynamic_dripping_lessons  = Sensei_Content_Drip()->utils->get_dripping_lessons_by_type( 'dynamic' );

		$this->assertSame( $absolute_lessons ,$absolute_dripping_lessons );
		$this->assertSame( $dynamic_lessons ,$dynamic_dripping_lessons );
	}
}
