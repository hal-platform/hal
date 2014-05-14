var gulp = require('gulp'),
    open = require('open'),
    plugins = require('gulp-load-plugins')(),
    lr = require('tiny-lr'),
    server = lr();

var onError = function (err) {
    gutil.beep();
    console.log(err);
};

gulp.task('styles', function() {
    return gulp.src('sass/**/*.scss')
        // handle errors so watch doesn't choke
        .pipe(plugins.plumber())
        .pipe(plugins.compass({
            css: 'public/css',
            sass: 'sass',
            image: 'public/img',
            javascript: 'public/js',
            require: ['modular-scale', 'breakpoint']
        }))
        .pipe(plugins.autoprefixer('last 2 versions', '> 1%'))
        .pipe(gulp.dest('public/css'))
        .pipe(plugins.livereload(server));
});

gulp.task('scripts', function() {
    return gulp.src(['public/js/**/*.js', '!./public/js/vendor/**/*.js'])
        .pipe(plugins.jshint())
        .pipe(plugins.jshint.reporter('default'))
        .pipe(plugins.livereload(server));
});

gulp.task('html', function() {
    return gulp.src('app/templates/**/*.twig')
        .pipe(plugins.livereload(server));
});

gulp.task('watch', function() {
    server.listen(35729, function (err) {
        if (err) {
            return console.log(err)
        };

        gulp.watch('sass/**/*.scss', ['styles']);
        gulp.watch('app/templates/**/*.twig', ['html']);
        gulp.watch('public/js/**/*.js', ['scripts']);
    });
});

gulp.task('serve', ['watch'], function() {
    open("http://hal9000.local");
});