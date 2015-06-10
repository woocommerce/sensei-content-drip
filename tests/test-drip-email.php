<?php
/**
* Testing the Sensei Content Drip ( scd ) Email functionality class
* @class Scd_Ext_Lesson_Admin
* @file ../includes/class-scd-ext-lesson-admin.php
*/
class LessonAdminTest extends WP_UnitTestCase {
	function testClassSetup() {
		$this->assertTrue( class_exists( 'Scd_Ext_Lesson_Admin' ) , 'The plugin class file was not loaded' );
		$this->assertTrue( isset( Sensei_Content_Drip()->drip_email )   , 'The plugin class was not intstantiated' );
	}
}