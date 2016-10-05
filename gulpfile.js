var gulp      = require( 'gulp' );
var rename    = require( 'gulp-rename' );
var uglify    = require( 'gulp-uglify' );
var minifyCSS = require( 'gulp-minify-css' );
var wpPot     = require( 'gulp-wp-pot' );
var sort      = require( 'gulp-sort' );
del           = require( 'del' );

var paths = {
	scripts: ['assets/js/*.js'],
	css: ['assets/css/*.css']
};

gulp.task( 'clean', function( cb ) {
	del( ['assets/js/*.min.js', 'assets/css/*.min.css'], cb );

});

gulp.task( 'default', [ 'clean' ] , function () {
	gulp.run( 'css' ) ;
	gulp.run( 'javascript' );
});

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
