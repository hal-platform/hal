var gulp = require('gulp'),
    open = require('open'),
    plugins = require('gulp-load-plugins')(),
    isDeploy = process.argv.indexOf('--deploy') > -1,
    exec = require('child_process').exec;

gulp.task('styles', function() {
    return gulp.src('sass/**/*.scss')
        // handle errors so watch doesn't choke
        .pipe(plugins.plumber())
        .pipe(plugins.compass({
            css: 'public/css',
            sass: 'sass',
            style: isDeploy ? 'compressed' : 'compact',
            require: ['modular-scale', 'breakpoint', 'singularitygs'],
            bundle_exec: true
        }))
        .pipe(plugins.autoprefixer({browsers: ['last 5 versions', '> 1%']}))
        .pipe(gulp.dest('public'));
});

gulp.task('webpack', ['cleanJS'], function() {
    return gulp.src('js/app.js')
        .pipe(plugins.webpack(
            require('./webpack.config.js')
        ))
        .pipe(gulp.dest('public/js/'));
});

gulp.task('scripts', ['jshint'], function() {
    gulp.start('webpack');
});

gulp.task('jshint', function() {
    return gulp.src(['js/app/*.js'])
        .pipe(plugins.jshint())
        .pipe(plugins.jshint.reporter('default'));
});

gulp.task('images', function() {
    return gulp.src('img/**/*')
        .pipe(gulp.dest('public/img'));
});

gulp.task('watch', function() {
    gulp.watch('sass/**/*.scss', ['styles']);
    gulp.watch('js/**/*.js', ['scripts']);
    gulp.watch('img/**/*', ['images']);
});

gulp.task('build', ['styles', 'scripts', 'images']);

gulp.task('clean', function() {
    return gulp.src(['public/js', 'public/img', 'public/css'], { read: false })
        .pipe(plugins.clean());
});

gulp.task('cleanJS', function() {
    return gulp.src(['public/js'], { read: false })
        .pipe(plugins.clean());
});

gulp.task('default', ['clean'], function() {
    gulp.start('build');
});
