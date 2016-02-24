module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        compass: {
            dist: {
                options: {
                    fontsPath: 'fonts',
                    sassDir: 'sass',
                    cssDir: '../../css',
                    imagesPath: '../../images',
                    httpStylesheetsPath: '/css',
                    httpImagesPath: '/images',
                    outputStyle: 'compressed',
                    importPath: [
                        '../../Lightning/Vendor/foundation/scss',
                        '../../Lightning/Vendor/compass/frameworks/compass/stylesheets',
                        '../../Lightning/Resources/sass'
                    ]
                }
            }
        },

        uglify: {
            dist: {
                files: {
                    '../../js/lightning.min.js': [
                        '../../Lightning/build/js-res/fastclick.min.js',
                        '../../Lightning/build/js-res/jquery.js',
                        '../../Lightning/build/js-res/placeholder.min.js',
                        '../../Lightning/build/js-res/modernizr.min.js',
                        '../../Lightning/build/js-res/foundation.js',
                        '../../Lightning/build/js-res/foundation.*.js',
                        '../../Lightning/build/js-res/lightning.min.js'
                    ],
                    '../../js/ckeditor/config.js': 'js/ckeditor_config.js',
                }
            },
        },

        copy: {
            dist: {
                files: [
                    {
                        cwd: '../../Lightning/build',
                        src: '**',
                        dest: '../../',
                        expand: true
                    }
                ]
            }
        },

        watch: {
            js: {
                files: 'js/**/*.js',
                tasks: 'uglify',
                options: {
                    spawn: false
                }
            },
            sass: {
                files: ['sass/**/*.scss', 'sass/**/*.css'],
                tasks: 'compass',
                options: {
                    spawn: false
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-compass');

    grunt.registerTask('build', ['compass', 'uglify', 'copy']);
    grunt.registerTask('default', ['build', 'watch']);
};
