#!/bin/bash

# From Jetpack package

mysql -u root -e "CREATE DATABASE wordpress_tests;"

SCRIPT_DIR="$( cd "$(dirname "$0")" ; pwd -P )"
CURRENT_DIR=$(pwd)

WP_SLUGS=('master' 'latest' 'previous')
SENSEI_SLUGS=('master' 'latest' 'previous')

if [ ! -z "$WP_VERSION" ]; then
	WP_SLUGS=("$WP_VERSION")
fi

if [ ! -z "$SENSEI_VERSION" ]; then
	SENSEI_SLUGS=("$SENSEI_VERSION")
fi


for SENSEI_SLUG in "${SENSEI_SLUGS[@]}"; do
	echo "Preparing Sensei $SENSEI_SLUG ...";

	cd $CURRENT_DIR/..

		rm -rf "/tmp/sensei-$SENSEI_SLUG"
	case $SENSEI_SLUG in
	master)
		git clone --depth=1 --branch master https://github.com/Automattic/Sensei.git /tmp/sensei-master
		;;
	latest)
			SENSEI_VERSION=`php $SCRIPT_DIR/get-sensei-version.php`
			echo "Version: $SENSEI_VERSION"
		git clone --depth=1 --branch version/$SENSEI_VERSION https://github.com/Automattic/Sensei.git /tmp/sensei-latest
		;;
	latest-v2)
			SENSEI_VERSION=`php $SCRIPT_DIR/get-sensei-version.php --latest-v2`
			echo "Version: $SENSEI_VERSION"
		git clone --depth=1 --branch version/$SENSEI_VERSION https://github.com/Automattic/Sensei.git /tmp/sensei-latest-v2
		;;
	previous)
		SENSEI_VERSION=`php $SCRIPT_DIR/get-sensei-version.php --previous`
			echo "Version: $SENSEI_VERSION"
		git clone --depth=1 --branch version/$SENSEI_VERSION https://github.com/Automattic/Sensei.git /tmp/sensei-previous
		;;
	esac
done

for WP_SLUG in "${WP_SLUGS[@]}"; do
	echo "Preparing WordPress $WP_SLUG ...";

	cd $CURRENT_DIR/..

		rm -rf "/tmp/wordpress-$WP_SLUG"
	case $WP_SLUG in
	master)
		git clone --depth=1 --branch master https://github.com/WordPress/wordpress-develop.git /tmp/wordpress-master
		;;
	latest)
		WORDPRESS_VERSION=`php $SCRIPT_DIR/get-wp-version.php`
		echo "Version: $WORDPRESS_VERSION"
		git clone --depth=1 --branch $WORDPRESS_VERSION https://github.com/WordPress/wordpress-develop.git /tmp/wordpress-latest
		;;
	previous)
		WORDPRESS_VERSION=`php $SCRIPT_DIR/get-wp-version.php --previous`
		echo "Version: $WORDPRESS_VERSION"
		git clone --depth=1 --branch $WORDPRESS_VERSION https://github.com/WordPress/wordpress-develop.git /tmp/wordpress-previous
		;;
	*)
		git clone --depth=1 --branch $WP_SLUG https://github.com/WordPress/wordpress-develop.git /tmp/wordpress-$WP_SLUG
		;;
	esac

	cp -r $PLUGIN_BASE_DIR "/tmp/wordpress-$WP_SLUG/src/wp-content/plugins/$PLUGIN_SLUG"
	cd /tmp/wordpress-$WP_SLUG

	cp wp-tests-config-sample.php wp-tests-config.php
	sed -i "s/youremptytestdbnamehere/wordpress_tests/" wp-tests-config.php
	sed -i "s/yourusernamehere/root/" wp-tests-config.php
	sed -i "s/yourpasswordhere//" wp-tests-config.php

	echo "Done!";
done

exit 0;
