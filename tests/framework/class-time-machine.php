<?php

namespace Scd_Ext\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Scd_Ext_Utils;

class Time_Machine extends Scd_Ext_Utils {
	/**
	 * Original object for utils.
	 * @var Scd_Ext_Utils
	 */
	private static $utils_original;

	/**
	 * Instance of this class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Current now time.
	 *
	 * @var DateTimeImmutable
	 */
	private static $now;

	/**
	 * Current time zone.
	 *
	 * @var DateTimeZone
	 */
	private static $timezone;

	/**
	 * Activate the time machine.
	 *
	 * @param bool $reset Reset the date.
	 */
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

	/**
	 * Deactivate the time machine.
	 */
	public static function deactivate() {
		if ( isset( self::$utils_original ) ) {
			Sensei_Content_Drip()->utils = self::$utils_original;
		}
	}

	/**
	 * Reset the time machine to use PHP now and timezone.
	 */
	public static function reset() {
		self::$now      = null;
		self::$timezone = null;
	}

	/**
	 * Set the current time for the time machine.
	 *
	 * @param DateTimeImmutable $datetime Date to set.
	 */
	public static function set_datetime( DateTimeImmutable $datetime ) {
		self::$now = $datetime;
	}

	/**
	 * Set the current time zone.
	 *
	 * @param DateTimeZone $timezone Time zone to set.
	 */
	public static function set_timezone( DateTimeZone $timezone ) {
		self::$timezone = $timezone;
	}

	/**
	 * Get the current datetime object.
	 *
	 * @return DateTimeImmutable
	 * @throws \Exception
	 */
	public function current_datetime() {
		if ( isset( self::$now ) ) {
			return self::$now;
		}

		return new DateTimeImmutable( 'now', $this->wp_timezone() );
	}

	/**
	 * Ge the current time zone object.
	 *
	 * @return DateTimeZone
	 */
	public function wp_timezone() {
		if ( isset( self::$timezone ) ) {
			return self::$timezone;
		}

		return parent::wp_timezone();
	}
}
