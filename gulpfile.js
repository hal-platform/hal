var gulp = require('gulp'),
    plugins = require('gulp-load-plugins')(),
    webpack = require('gulp-webpack-build'),
    path = require('path'),
    isDeploy = process.argv.indexOf('--deploy') > -1,
    isWatch = process.argv.indexOf('--watch') > -1,
    del = require('del');

// javascript
gulp.task('js-hint', function() {
    return gulp.src(['js/app/*.js'])
        .pipe(plugins.jshint())
        .pipe(plugins.jshint.reporter('default'));
});

gulp.task('js-clean', function(cb) {
    del([
        'js/nunjucks-dist',
        'public/js',
    ], cb);
});

gulp.task('js-webpack', ['js-nunjucks-clean'], function() {
    var webpackOptions = {
        debug: isDeploy ? false : true,
        devtool: isDeploy ? '' : '#source-map',
        watchDelay: 200
    },
    webpackConfig = {
        useMemoryFs: true
    };

    if (isWatch) {
        gulp.watch('js/**/*.js').on('change', function(event) {
            if (event.type === 'changed') {
                gulp.src(event.path, { base: path.resolve('js') })
                    .pipe(webpack.closest('webpack.config.js'))
                    .pipe(webpack.configure(webpackConfig))
                    .pipe(webpack.overrides(webpackOptions))
                    .pipe(webpack.watch(function(err, stats) {
                        gulp.src(this.path)
                            .pipe(webpack.proxy(err, stats))
                            .pipe(webpack.format({ version: false, timings: true }))
                            .pipe(gulp.dest('public/js/'));
                    }));
            }
        });
    } else {
        return gulp.src('./webpack.config.js')
            .pipe(webpack.configure(webpackConfig))
            .pipe(webpack.overrides(webpackOptions))
            .pipe(webpack.compile())
            .pipe(webpack.format({ version: false, timings: true }))
            .pipe(webpack.failAfter({ errors: true, warnings: false }))
            .pipe(gulp.dest('public/js/'));
    }
});

gulp.task('js-nunjucks', function() {
    return gulp.src('js/nunjucks-html/*.html')
        .pipe(plugins.nunjucks())
        .pipe(gulp.dest('js/nunjucks-dist'));
});

// this is stupid
gulp.task('js-nunjucks-clean', ['js-clean'], function() {
    return gulp.src('js/nunjucks-html/*.html')
        .pipe(plugins.nunjucks())
        .pipe(gulp.dest('js/nunjucks-dist'));
});

gulp.task('js', ['js-hint'], function() {
    gulp.start('js-webpack');
});

// css
gulp.task('css', function() {
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
    gulp.watch('sass/**/*.scss', ['css']);
    gulp.watch('js/**/*.js', ['js']);
});

gulp.task('build', ['css', 'js']);

gulp.task('clean', ['js-clean'], function(cb) {
    del([
        'public/js',
        'public/css'
    ], cb);
});

gulp.task('default', ['clean'], function() {
    gulp.start('build');
});
