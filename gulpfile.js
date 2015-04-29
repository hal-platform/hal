var gulp = require('gulp'),
    plugins = require('gulp-load-plugins')(),
    webpack = require('gulp-webpack-build'),
    isDeploy = process.argv.indexOf('--deploy') > -1,
    del = require('del');

// javascript
gulp.task('js-hint', function() {
    return gulp.src(['js/app/*.js'])
        .pipe(plugins.jshint())
        .pipe(plugins.jshint.reporter('default'));
});

gulp.task('js-clean', function(cb) {
    del('public/js', cb);
});

gulp.task('js-webpack', ['js-clean'], function() {
    var webpackOptions = {
        debug: isDeploy ? false : true,
        devtool: isDeploy ? '' : '#source-map',
        watchDelay: 200
    },
    webpackConfig = {
        useMemoryFs: true
    };

    return gulp.src('./webpack.config.js')
        .pipe(webpack.configure(webpackConfig))
        .pipe(webpack.overrides(webpackOptions))
        .pipe(webpack.compile())
        .pipe(webpack.format({ version: false, timings: true }))
        .pipe(webpack.failAfter({ errors: true, warnings: false }))
        .pipe(gulp.dest('public/js/'));
});

gulp.task('scripts', ['js-hint'], function() {
    gulp.start('js-webpack');
});

// css
gulp.task('styles', function() {
    return gulp.src('sass/**/*.scss')
        .pipe(plugins.sass({
            errLogToConsole: true,
            outputStyle: isDeploy ? 'compressed' : 'compact',
            precision: 2
        }))
        .pipe(plugins.autoprefixer({browsers: ['last 5 versions', '> 1%']}))
        .pipe(gulp.dest('public/css'));
});

// core
gulp.task('watch', function() {
    gulp.watch('sass/**/*.scss', ['styles']);
    gulp.watch('js/**/*.js', ['scripts']);
});

gulp.task('build', ['styles', 'scripts']);

gulp.task('clean', function(cb) {
    del([
        'public/js',
        'public/css'

    ], cb);
});

gulp.task('default', ['clean'], function() {
    gulp.start('build');
});
