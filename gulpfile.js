var gulp = require('gulp'),
    sequence = require('run-sequence'),
    webpack = require('gulp-webpack-build'),
    jshint = require('gulp-jshint'),
    nunjucks = require('gulp-nunjucks'),

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
    webpackConfigFile = webpack.config.CONFIG_FILENAME;

// javascript
var webpackOptions = {
    debug: isDeploy ? false : true,
    devtool: isDeploy ? '' : '#source-map',
    watchDelay: 200
  },
  webpackConfig = {
    useMemoryFs: true
  };

gulp.task('jshint', function() {
  return gulp.src([
        path.join(srcJS, '**/*.js'),
        '!' + path.join(srcJS, 'nunjucks-dist/*.js'),
        '!' + path.join(srcJS, 'app/util/time-duration.js'),
    ])
    .pipe(jshint())
    .pipe(jshint.reporter('jshint-stylish'))
    .pipe(jshint.reporter('fail'));
});

gulp.task('js-clean', function(cb) {
    return del(['js/nunjucks-dist'], cb);
});

gulp.task('js-webpack', ['js-nunjucks'], function() {
  return gulp.src(path.join(srcJS, webpackConfigFile), { base: path.resolve(srcJS) })
    .pipe(webpack.init(webpackConfig))
    .pipe(webpack.props(webpackOptions))
    .pipe(webpack.run())
    .pipe(webpack.format({ version: false, timings: true }))
    .pipe(webpack.failAfter({ errors: true, warnings: false }))
    .pipe(gulp.dest(distJS));
});

gulp.task('js-watch', function() {
    gulp.watch(path.join(srcJS, '**/*.*')).on('change', function(event) {
      if (event.type === 'changed') {
        gulp.src(event.path, { base: path.resolve(srcJS) })
          .pipe(webpack.closest(webpackConfigFile))
          .pipe(webpack.init(webpackConfig))
          .pipe(webpack.props(webpackOptions))
          .pipe(webpack.watch(function(err, stats) {
            gulp.src(this.path)
              .pipe(webpack.proxy(err, stats))
              .pipe(webpack.format({ version: false, timings: true }))
              .pipe(gulp.dest(distJS));
          }));
      }
    });
});

// gulp.task('webpack', ['js-webpack']);

gulp.task('js', function(cb) {
    sequence('jshint', ['js-webpack'], cb);
});

gulp.task('js-nunjucks', ['js-clean'], function() {
    return gulp.src('js/nunjucks-html/*.html')
        .pipe(nunjucks())
        .pipe(gulp.dest('js/nunjucks-dist'));
});

// css
gulp.task('css', function() {
    return gulp.src(srcCSS + '/style.scss')
        .pipe(sass.sync({
            errLogToConsole: true,
            outputStyle: isDeploy ? 'compressed' : 'compact',
            precision: 2
        }))
        .pipe(autoprefixer({browsers: ['last 5 versions', '> 1%']}))
        .pipe(gulp.dest(distCSS));
});

// core
gulp.task('watch', function() {
    gulp.watch('sass/**/*.scss', ['css']);
    gulp.start('js-watch');
});

gulp.task('build', function(cb) {
  sequence('clean', ['css', 'js'], cb);
});

gulp.task('clean', ['js-clean'], function(cb) {
    del([distJS, distCSS], cb);
});

gulp.task('default', ['build']);
