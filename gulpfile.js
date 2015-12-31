var gulp = require('gulp'),
    gulputil = require('gulp-util'),

    sequence = require('run-sequence'),
    plumber = require('gulp-plumber'),
    webpack = require('webpack'),
    eslint = require('gulp-eslint'),

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

gulp.task('js:lint', function() {
  return gulp.src([
        path.join(srcJS, '**/*.{js,jsx}'),
        '!js/webpack.config.js'
    ])
    .pipe(eslint())
    .pipe(eslint.format())
    .pipe(eslint.failAfterError());
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
    return sequence('js:lint', ['js:webpack'], callback);
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
        path.join(srcJS, '**/*.{js,jsx,nunj}'),
        ['js:webpack']
    );

});

gulp.task('build', function(callback) {
    gulputil.log('[build]', isDeploy ? 'Building for production' : 'Building for development');

    return sequence('clean', ['css', 'js'], callback);
});

gulp.task('clean', function(callback) {
    return del([distJS, distCSS], callback);
});
