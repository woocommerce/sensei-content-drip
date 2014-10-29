<?php
/**
* Testing the Sensei Content Drip ( scd ) Email functionality class
* @class Scd_Ext_drip_email
* @file ../includes/class-scd-ext-drip-email.php
*/
class DripEmailTest extends WP_UnitTestCase {
	/**
	*
	*/
	function testClassSetup() {
		$this->assertTrue( class_exists( 'Scd_Ext_drip_email' ) , 'The plugin class file was not loaded' );
		$this->assertTrue( isset( Sensei_Content_Drip()->drip_email )   , 'The plugin class was not intstantiated' );
	}
}