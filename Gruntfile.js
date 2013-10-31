module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Watch Task
        watch: {
            sass: {
                files: ['sass/**/*.scss'],
                tasks: ['compass:dev'],
            },
        },

        // Compass Task
        compass: {
            dev: {
                options: {
                    httpPath: 'public',
                    cssDir: 'public/css',
                    sassDir: 'sass',
                    imagesDir: 'public/img',
                    javascriptsDir: 'public/js',
                    force: true,
                    outputStyle: 'compact',
                    relativeAssets: true,
                    require: ['modular-scale', 'singularitygs', 'breakpoint'],
                },
            },
            dist: {
                options: {
                    outputStyle: 'compressed',
                },
            },
        },

        // Uglify Task
        uglify: {
            head: {
                files: {
                    'public/js/head.min.js': ['bower_components/modernizr/modernizr.js', 'bower_components/respond/respond.src.js']
                },
            },
            main: {
                files: {
                    'public/js/main.min.js':
                        [
                        'public/js/plugins.js',
                        'public/js/main.js'
                        ],
                },
            },
            jquery: {
                files: {
                    'public/js/vendor/jquery-2.0.3.min.js': ['bower_components/jquery/jquery.js']
                },
            },
        },

    });
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-compass');
    grunt.loadNpmTasks('grunt-contrib-uglify');
};

