#!/bin/bash

set -e

run_phpunit_for() {
	wp_test_branch="$1";
	sensei_test_branch="$2";

	echo "Testing on Sensei LMS ($sensei_test_branch); WordPress ($wp_test_branch)..."
	export WP_TESTS_DIR="/tmp/wordpress-$wp_test_branch/tests/phpunit"
	export SENSEI_PLUGIN_DIR="/tmp/sensei-$sensei_test_branch"

	cd "/tmp/wordpress-$wp_test_branch/src/wp-content/plugins/$PLUGIN_SLUG"

	if [ ! -z "$PHPUNIT_GROUP" ]; then
		phpunit --group="$PHPUNIT_GROUP"
	else
		phpunit
	fi

	if [ $? -ne 0 ]; then
		exit 1
	fi
}

WP_SLUGS=('master' 'latest' 'previous')
SENSEI_SLUGS=('master' 'latest' 'previous')

if [ ! -z "$WP_VERSION" ]; then
	WP_SLUGS=("$WP_VERSION")
fi

if [ ! -z "$SENSEI_VERSION" ]; then
	SENSEI_SLUGS=("$SENSEI_VERSION")
fi

for WP_SLUG in "${WP_SLUGS[@]}"; do
	for SENSEI_SLUG in "${SENSEI_SLUGS[@]}"; do
		run_phpunit_for "$WP_SLUG" "$SENSEI_SLUG"
	done
done

exit 0
