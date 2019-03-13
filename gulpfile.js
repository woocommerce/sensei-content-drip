var gulp        = require( 'gulp' );
var rename      = require( 'gulp-rename' );
var uglify      = require( 'gulp-uglify' );
var minifyCSS   = require( 'gulp-minify-css' );
var wpPot       = require( 'gulp-wp-pot' );
var sort        = require( 'gulp-sort' );
var zip         = require( 'gulp-zip' );
var runSequence = require( 'run-sequence' );
var del         = require( 'del' );

const paths = {
	scripts: [ 'assets/js/*.js' ],
	css: [ 'assets/css/*.css' ],
	buildDir: 'build/sensei-content-drip',
	packageZip: 'build/sensei-content-drip.zip',
};

gulp.task( 'clean', function() {
	del( [ 'assets/js/*.min.js', 'assets/css/*.min.css', 'build' ] );
});

gulp.task( 'default', [ 'clean' ] , function () {
	gulp.run( 'css' ) ;
	gulp.run( 'javascript' );
});

gulp.task( 'copy', function() {
	return gulp.src( [ '**/*.php', 'assets', '!node_modules/**', '!build/**', '!vendor/**' ] )
		.pipe( gulp.dest( paths.buildDir ) );
} );

gulp.task( 'css', function () {
	return gulp.src( paths.css )
		.pipe( minifyCSS( { keepBreaks: false } ) )
		.pipe( rename( { extname: '.min.css' } ) )
		.pipe( gulp.dest( 'assets/css' ) );
});

gulp.task( 'javascript', function () {
	 return gulp.src( paths.scripts )
		// This will minify and rename to *.min.js
		.pipe( uglify() )
		.pipe( rename( { extname: '.min.js' } ) )
		.pipe( gulp.dest( 'assets/js' ) );
});

gulp.task( 'pot', function () {
	return gulp.src( [ '**/**.php', '!node_modules/**'] )
		.pipe( sort() )
		.pipe( wpPot( {
			domain:    'sensei-content-drip',
			bugReport: 'https://www.transifex.com/woothemes/sensei-by-woothemes/'
		} ) )
		.pipe( gulp.dest( 'lang' ) );
});

gulp.task( 'build', function( cb ) {
	runSequence( 'clean', [ 'css', 'javascript' ], 'pot', 'copy', cb );
} );

gulp.task( 'zip-package', function() {
	return gulp.src( paths.buildDir + '/**/*', { base: paths.buildDir + '/..' } )
		.pipe( zip( paths.packageZip ) )
		.pipe( gulp.dest( '.' ) );
} );

gulp.task( 'package', function( cb ) {
	runSequence( 'build', 'zip-package', cb );
} );
