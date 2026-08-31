/**
 * Gulp build for Account Customizer for WooCommerce (ES module).
 *
 *   npm install          Install the toolchain.
 *   npx gulp build       Compile SCSS -> CSS and minify JS.
 *   npx gulp watch       Rebuild on change.
 *   npx gulp zip         Build, then package a distributable zip in dist/.
 *   npx gulp             Default: build.
 */

import gulp from 'gulp';
import gulpSass from 'gulp-sass';
import * as sassCompiler from 'sass';
import autoprefixer from 'gulp-autoprefixer';
import cleanCSS from 'gulp-clean-css';
import terser from 'gulp-terser';
import rename from 'gulp-rename';
import zip from 'gulp-zip';
import { deleteAsync } from 'del';

const { src, dest, series, parallel, watch: gulpWatch } = gulp;
const sass = gulpSass( sassCompiler );

const SLUG = 'account-customizer-for-woocommerce';

/* Hand-authored JS that should be minified. Bundled libs (select2, fontawesome) are skipped. */
const OWN_JS = [
	'assets/js/admin.js',
	'assets/js/frontend.js',
	'assets/js/customize-controls.js',
];

/* Files that ship in the plugin zip. */
const DIST_GLOBS = [
	'**/*',
	'!node_modules/**',
	'!assets/scss/**',
	'!assets/src/**',
	'!dist/**',
	'!submission/**',
	'!vendor/**',
	'!tests/**',
	'!composer.json',
	'!composer.lock',
	'!phpunit.xml.dist',
	'!.phpunit.cache/**',
	'!.git/**',
	'!.github/**',
	'!.sass-cache/**',
	'!gulpfile.mjs',
	'!package.json',
	'!package-lock.json',
	'!pnpm-lock.yaml',
	'!.gitignore',
	'!.editorconfig',
	'!.browserslistrc',
	'!phpcs.xml.dist',
	'!phpcs.xml',
	'!CLAUDE.md',
	'!DOCUMENTATION.docx',
	'!**/*.map',
];

/* SCSS -> CSS (expanded + prefixed) and a minified .min.css sibling. */
export function styles() {
	return src( 'assets/scss/*.scss' )
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( autoprefixer() )
		.pipe( dest( 'assets/css' ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/css' ) );
}

/* Minify the hand-authored JS to *.min.js siblings. */
export function scripts() {
	return src( OWN_JS, { base: 'assets/js' } )
		.pipe( terser() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/js' ) );
}

function cleanDist() {
	return deleteAsync( [ 'dist' ] );
}

/* Package a zip with the plugin folder as the top-level directory. */
function makeZip() {
	return src( DIST_GLOBS, { base: '.', dot: true } )
		.pipe( rename( ( p ) => { p.dirname = SLUG + '/' + p.dirname; } ) )
		.pipe( zip( SLUG + '.zip' ) )
		.pipe( dest( 'dist' ) );
}

export function watch() {
	gulpWatch( 'assets/scss/**/*.scss', styles );
	gulpWatch( OWN_JS, scripts );
}

export const build = parallel( styles, scripts );
export const zipTask = series( build, cleanDist, makeZip );
export { zipTask as zip };
export default build;
