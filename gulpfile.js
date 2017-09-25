var gulp = require( 'gulp' ),

    replace = require( 'gulp-replace' ),
    del = require( 'del' ),
    concat = require( 'gulp-concat' ),
    gettext = require( 'gulp-gettext' );

/**
 * Compile .po to .mo  files.
 */
var poFiles = ['./**/languages/*.po'];
gulp.task( 'po2mo', function() {
    return gulp.src(poFiles)
        .pipe( gettext() )
        .pipe( gulp.dest( '.' ) )
} );

/**
 * Task to clear build/ folder.
 */
gulp.task( 'clear:build', function() {
    del.sync( 'build/**/*' );
} );

/**
 * Build.
 */
gulp.task( 'build', ['clear:build', 'po2mo'], function() {
    // collect all needed files
    gulp.src( [
        '**/*',
        '!*.md',
        '!readme.txt',
        '!gulpfile.js',
        '!package.json',
        '!.gitignore',
        '!node_modules{,/**}',
        '!build{,/**}',
        '!assets{,/**}'
    ] ).pipe( gulp.dest( 'build/' ) );

    // concat files for WP's readme.txt
    // manually validate output with https://wordpress.org/plugins/about/validator/
    gulp.src( [ 'readme.txt', 'README.md', 'CHANGELOG.md' ] )
        .pipe( concat( 'readme.txt' ) )
        // remove screenshots
        // todo: scrennshot section for WP's readme.txt
        .pipe( replace( /\n\!\[image\]\([^)]+\)\n/g, '' ) )
        // WP markup
        .pipe( replace( /#\s*(Changelog)/g, "## $1" ) )
        .pipe( replace( /###\s*([^(\n)]+)/g, "=== $1 ===" ) )
        .pipe( replace( /##\s*([^(\n)]+)/g, "== $1 ==" ) )
        .pipe( replace( /==\s(Unreleased|[0-9\s\.-]+)\s==/g, "= $1 =" ) )
        .pipe( replace( /#\s*[^\n]+/g, "== Description ==" ) )
        .pipe( gulp.dest( 'build/' ) );
} );

/**
 * Watch tasks.
 */
gulp.task( 'default', ['po2mo'], function() {
    gulp.watch( poFiles, ['po2mo'] );
} );
