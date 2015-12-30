var gulp = require('gulp'),
    gulputil = require('gulp-util'),

    sequence = require('run-sequence'),
    plumber = require('gulp-plumber'),
    webpack = require('webpack'),
    jshint = require('gulp-jshint'),

    sass = require('gulp-sass'),
    autoprefixer = require('gulp-autoprefixer'),

    path = require('path'),
    del = require('del');

var isDeploy = process.argv.indexOf('--deploy') > -1,
    assets = 'public',
    srcJS = 'js',
    distJS = assets + '/js',
    srcCSS = 'sass',
    distCSS = assets + '/css',
    webpackConfig = require('./' + path.join(srcJS, 'webpack.config.js'));

// javascript
webpackConfig = Object.create(webpackConfig);
webpackConfig.debug = isDeploy ? false : true;
webpackConfig.devtool = isDeploy ? '' : 'eval-source-map';

gulp.task('js:hint', function() {
  return gulp.src([
        path.join(srcJS, '**/*.js'),
    ])
    .pipe(jshint({
        sub: true,
        esnext: true
        // esversion: 6
    }))
    .pipe(jshint.reporter('jshint-stylish'))
    .pipe(jshint.reporter('fail'));
});

var webpackCompiler = webpack(webpackConfig);
gulp.task('js:webpack', function(callback) {
    webpackCompiler.run(function(err, stats) {
        if (err) throw new gulputil.PluginError('js-webpack', err);
        gulputil.log('[js-webpack]', stats.toString({
            colors: true
        }));
        callback();
    });
});

gulp.task('js', function(callback) {
    sequence('js:hint', ['js:webpack'], callback);
});

// css
gulp.task('css', function() {
    return gulp.src(srcCSS + '/style.scss')
        .pipe(plumber())
        .pipe(sass.sync({
            includePaths: ['./node_modules'],
            errLogToConsole: true,
            outputStyle: isDeploy ? 'compressed' : 'compact',
            precision: 2
        }))
        .pipe(autoprefixer({browsers: ['last 5 versions', '> 1%']}))
        .pipe(gulp.dest(distCSS));
});

// core
gulp.task('watch', function() {
    gulp.watch(
        path.join(srcCSS, '**/*.scss'),
        ['css']
    );
    gulp.watch(
        [
            path.join(srcJS, '**/*.js'),
            path.join(srcJS, '**/*.nunj')
        ],
        ['js:webpack']
    );

});

gulp.task('build', function(callback) {
  sequence('clean', ['css', 'js'], callback);
});

gulp.task('clean', function(callback) {
    del([distJS, distCSS], callback);
});

gulp.task('default', ['build']);
