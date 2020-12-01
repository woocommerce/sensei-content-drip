<?php

namespace Scd_Ext\Tests;

use DateTimeImmutable;

/**
 * Set up a lesson to drop on a specific date.
 *
 * @param int               $lesson_id Lesson post ID.
 * @param DateTimeImmutable $date      Date object to set.
 */
function set_absolute_drip_date( $lesson_id, DateTimeImmutable $date ) {
	$date = $date->setTime( 0, 0, 0 );

	$data =  array(
		'_sensei_content_drip_type'         => 'absolute',
		'_sensei_content_drip_details_date' => $date->getTimestamp(),
	);

	foreach( $data as $key => $value ) {
		update_post_meta( $lesson_id, $key, $value );
	}
}

/**
 *
 * Set up a lesson to drop on a relative/dynamic date.
 *
 * @param int    $lesson_id Lesson post ID.
 * @param string $unit      Unit to use (day, month, year).
 * @param int    $amount    Unit amount.
 */
function set_dynamic_drip_date( $lesson_id, $unit, $amount ) {
	$data = [
		'_sensei_content_drip_type'                     => 'dynamic',
		'_sensei_content_drip_details_date_unit_type'   => $unit,
		'_sensei_content_drip_details_date_unit_amount' => $amount,
	];

	foreach( $data as $key => $value ) {
		update_post_meta( $lesson_id, $key, $value );
	}
}
