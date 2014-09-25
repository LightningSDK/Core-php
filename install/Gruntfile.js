
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
                        '../../Lightning/sass'
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
                    '../../js/lightning.min.js': '../../Lightning/JSSource/*.js',
                }
            },
            vendor: {
                files: {
                    '../../js/foundation.min.js': '../../Lightning/Vendor/foundation/js/foundation/foundation.js',
                    '../../js/modernizr.min.js': '../../Lightning/Vendor/foundation/vendor/modernizr/modernizr.js',
                    '../../js/placeholder.min.js': '../../Lightning/Vendor/foundation/vendor/jquery-placeholder/jquery.placeholder.js',
                    '../../js/fastclick.min.js': '../../Lightning/Vendor/foundation/vendor/fastclick/lib/fastclick.js',
                    '../../js/jquery.cookie.min.js': '../../Lightning/Vendor/foundation/vendor/jquery.cookie/jquery.cookie.js',
                    '../../js/jquery.min.js': '../../Lightning/Vendor/foundation/vendor/jquery/dist/jquery.js'
                }
            }
        },

        copy: {
            dist: {
                files: [
                ]
            }
        },

        watch: {
            js: {
                files: '**/*.js',
                tasks: 'uglify',
                options: {
                    spawn: false
                }
            },
            sass: {
                files: ['**/*.scss', '**/*.css'],
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

    grunt.registerTask('default', ['compass', 'uglify', 'copy', 'watch']);
};
