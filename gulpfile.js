var gulp = require('gulp'),
    open = require('open'),
    plugins = require('gulp-load-plugins')(),
    lr = require('tiny-lr'),
    server = lr(),
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
            require: ['modular-scale', 'breakpoint'],
            bundle_exec: true
        }))
        .pipe(plugins.autoprefixer('last 2 versions', '> 1%'))
        .pipe(gulp.dest('public/css'))
        .pipe(plugins.livereload(server));
});

gulp.task('scripts', ['cleanJS', 'optimizeJS'], function() {
    gulp.start('jshint');
});

gulp.task('jshint', function() {
    return gulp.src(['js/**/*.js', '!./js/vendor/**/*.js'])
        .pipe(plugins.jshint())
        .pipe(plugins.jshint.reporter('default'))
        .pipe(plugins.livereload(server));
});

gulp.task('html', function() {
    return gulp.src('app/templates/**/*.twig')
        .pipe(plugins.livereload(server));
});

gulp.task('images', function() {
    return gulp.src('img/**/*')
        .pipe(plugins.imagemin({
            optimizationLevel: 3,
            progressive: true,
            interlaced: true
        }))
        .pipe(gulp.dest('public/img'));
});

gulp.task('optimizeJS', function(cb) {
    exec('node node_modules/.bin/r.js -o js/optimizer.json', function (err, stdout, stderr) {
        console.log(stdout);
        console.log(stderr);
        cb(err);
    });
});

gulp.task('watch', function() {
    server.listen(35729, function (err) {
        if (err) {
            return console.log(err)
        };

        gulp.watch('sass/**/*.scss', ['styles']);
        gulp.watch('app/templates/**/*.twig', ['html']);
        gulp.watch('js/**/*.js', ['scripts']);
        gulp.watch('img/**/*', ['images']);
    });
});

gulp.task('serve', ['watch'], function() {
    open("http://hal9000.local");
});

gulp.task('build', ['styles', 'scripts', 'images'], function(cb) {
    if(isDeploy == false) {
        exec('node node_modules/.bin/r.js -o js/optimizer.json', function (err, stdout, stderr) {
            console.log(stdout);
            console.log(stderr);
            cb(err);
        });
    }
});

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
