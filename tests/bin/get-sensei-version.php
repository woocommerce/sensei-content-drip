<?php
// Modified from Jetpack
// @todo update the plugin slug to the final version
$ch = curl_init();
curl_setopt( $ch, CURLOPT_URL, 'https://api.wordpress.org/plugins/info/1.0/sensei-lms.json' );

// Set so curl_exec returns the result instead of outputting it.
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

// Get the response and close the channel.
$response = curl_exec( $ch );
curl_close( $ch );

$info     = json_decode( $response, true );
$versions = array_keys( $info['versions'] );

// Sorting available WordPress offers by version number
function offer_version_sort( $first, $second ) {
	return version_compare( $first, $second, '<' );
}

uasort( $versions, 'offer_version_sort' );
$version_stack = [];

foreach ( $versions as $version ) {
	if ( 'trunk' === $version ) {
		continue;
	}

	list( $major, $minor ) = explode( '.', $version );

	$base = $major . '.' . $minor;

	if (
		! isset( $version_stack[ $base ] )
		|| version_compare( $version, $version_stack[ $base ], '>' ) ) {

		// There is no version like this yet or there is a newer patch to this major version
		$version_stack[ $base ] = $version;
	}
}

$wp_versions = array_values( $version_stack );

if ( empty( $argv[1] ) ) {
	echo $wp_versions[0] . "\n";
} elseif ( '--previous' === $argv[1] ) {
	echo $wp_versions[1] . "\n";
} else {
	die(
		'Unknown argument: ' . $argv[1] . "\n"
		. "Use with no arguments to get the latest stable Sensei LMS version or `--previous' to get the previous stable major release.\n"
	);
}
