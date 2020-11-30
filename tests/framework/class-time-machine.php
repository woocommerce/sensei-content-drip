<?php

namespace Scd_Ext\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Scd_Ext_Utils;

class Time_Machine extends Scd_Ext_Utils {
	private static $utils_original;
	private static $instance;
	private static $now;
	private static $timezone;

	public static function activate( $reset = true ) {
		if ( ! isset( self::$utils_original ) ) {
			self::$utils_original = Sensei_Content_Drip()->utils;
		}

		if ( ! isset( self::$instance ) ) {
			self::$instance = new static;
		}

		Sensei_Content_Drip()->utils = self::$instance;

		if ( $reset ) {
			self::reset();
		}
	}

	public static function deactivate() {
		if ( isset( self::$utils_original ) ) {
			Sensei_Content_Drip()->utils = self::$utils_original;
		}
	}

	public static function reset() {
		self::$now      = null;
		self::$timezone = null;
	}

	public static function set_datetime( DateTimeImmutable $datetime ) {
		self::$now = $datetime;
	}

	public static function set_timezone( DateTimeZone $timezone ) {
		self::$timezone = $timezone;
	}

	public function current_datetime() {
		if ( isset( self::$now ) ) {
			return self::$now;
		}

		return new DateTimeImmutable( 'now', $this->wp_timezone() );
	}

	public function wp_timezone() {
		if ( isset( self::$timezone ) ) {
			return self::$timezone;
		}

		return parent::wp_timezone();
	}


}
