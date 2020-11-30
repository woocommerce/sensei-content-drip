<?php

namespace Scd_Ext\Tests;

use DateTimeImmutable;

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
