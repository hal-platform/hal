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

    });
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-compass');
};

