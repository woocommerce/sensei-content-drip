const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

const files = {
	'js/admin-lesson': 'js/admin-lesson.js',
	'js/admin-manual-drip': 'js/admin-manual-drip.js',
	'css/admin-lesson': 'css/admin-lesson.scss',
	'css/jquery-ui': 'css/jquery-ui.scss',
};

const baseDist = 'assets/dist/';

Object.keys( files ).forEach( function ( key ) {
	files[ key ] = path.resolve( './assets', files[ key ] );
} );

const FileLoader = {
	test: /\.(?:gif|jpg|jpeg|png|svg|woff|woff2|eot|ttf|otf)$/i,
	loader: 'file-loader',
	options: {
		name: '[path]/[name]-[contenthash].[ext]',
		context: 'assets',
		publicPath: '..',
	},
};

module.exports = {
	...defaultConfig,
	entry: files,
	output: {
		path: path.resolve( '.', baseDist ),
	},
	module: {
		rules: [ FileLoader, ...defaultConfig.module.rules ],
	},
};
