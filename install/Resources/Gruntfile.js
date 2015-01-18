
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
            options: {
                preserveComments: 'some'
            },
            dist: {
                files: {
                    '../../js/ckeditor/config.js': 'js/ckeditor_config.js',
                }
            },
        },

        copy: {
            dist: {
                files: [
                    {
                        src: [
                            '../../Lightning/build/js/*',
                            '../../Lightning/Vendor/build/*/*.js',
                        ],
                        dest: '../../js',
                        expand: true,
                        flatten: true,
                        filter:'isFile'
                    },
                    {
                        src: [
                            '../../Lightning/build/css/*',
                            '../../Lightning/Vendor/build/*/*.css'
                        ],
                        dest: '../../css',
                        expand: true,
                        flatten: true,
                        filter:'isFile'
                    },
                    {
                        src: [
                            '../../Lightning/Vendor/build/*/*.swf'
                        ],
                        dest: '../../swf',
                        expand: true,
                        flatten: true,
                        filter:'isFile'
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
