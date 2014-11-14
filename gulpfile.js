var gulp = require('gulp'),
    open = require('open'),
    plugins = require('gulp-load-plugins')(),
    lr = require('tiny-lr'),
    server = lr(),
    isDeploy = process.argv.indexOf('--deploy') > -1,
    exec = require('child_process').exec;

gulp.task('styles', function() {
    var g = gulp.src('sass/**/*.scss')
        // handle errors so watch doesn't choke
        .pipe(plugins.plumber())
        .pipe(plugins.compass({
            css: 'public/css',
            sass: 'sass',
            style: isDeploy ? 'compressed' : 'compact',
            require: ['modular-scale', 'breakpoint', 'singularitygs'],
            bundle_exec: true
        }))
        .pipe(plugins.autoprefixer('last 5 versions', '> 1%'))
        .pipe(gulp.dest('public/css'));

    if (isDeploy === false) {
        g.pipe(plugins.livereload(server));
    }

    return g;
});

gulp.task('scripts', ['cleanJS', 'cachebustJS', 'optimizeJS'], function() {
    gulp.start('jshint');
});

gulp.task('jshint', function() {
    var g = gulp.src(['js/**/*.js', '!./js/vendor/**/*.js'])
        .pipe(plugins.jshint())
        .pipe(plugins.jshint.reporter('default'));

    if (isDeploy === false) {
        g.pipe(plugins.livereload(server));
    }

    return g;
});

gulp.task('html', function() {
    return gulp.src('app/templates/**/*.twig')
        .pipe(plugins.livereload(server));
});

gulp.task('images', function() {
    return gulp.src('img/**/*')
        .pipe(gulp.dest('public/img'));
});

gulp.task('optimizeJS', ['cachebustJS'], function(cb) {
    var flags = "";
    if(isDeploy == false) {
        // override optimize setting if not deploy
        flags = " optimize=none"
    }

    exec('node node_modules/.bin/r.js -o js/optimizer.json' + flags, function (err, stdout, stderr) {
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

gulp.task('cachebustJS', function() {

    var sha = process.env.HAL_COMMIT;
    if (sha === undefined || !sha) {
        sha = 'dev' + (new Date()).getTime();
    }

    return gulp.src('js/require-config-default.json')
        .pipe(plugins.rename('require-config.json'))
        .pipe(plugins.jsonEditor(function(json) {
            json.urlArgs = 'v=' + sha;
            return json;
        }))
      .pipe(gulp.dest('js/'));
});

gulp.task('default', ['clean'], function() {
    gulp.start('build');
});
