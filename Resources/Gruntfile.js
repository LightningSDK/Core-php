
module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        compass: {
            dist: {
                options: {
                    fontsPath: 'fonts',
                    sassDir: 'sass',
                    cssDir: '../build/css',
                    imagesPath: '../build/images',
                    httpStylesheetsPath: '/css',
                    httpImagesPath: '/images',
                    outputStyle: 'compressed',
                    importPath: [
                        '../Vendor/foundation/scss',
                        '../Vendor/compass/frameworks/compass/stylesheets'
                    ]
                }
            }
        },

        uglify: {
            options: {
                preserveComments: 'some'
            },
            vendor: {
                files: {
                    '../build/js/lightning.min.js': ['init.js', 'js/*.js'],
                    '../build/js/foundation.min.js': [
                        '../Vendor/foundation/js/foundation/foundation.js',
                        '../Vendor/foundation/js/foundation/foundation.*.js'
                    ],
                    '../build/js/modernizr.min.js': '../Vendor/foundation/vendor/modernizr/modernizr.js',
                    '../build/js/placeholder.min.js': '../Vendor/foundation/vendor/jquery-placeholder/jquery.placeholder.js',
                    '../build/js/fastclick.min.js': '../Vendor/foundation/vendor/fastclick/lib/fastclick.js',
                    '../build/js/jquery.cookie.min.js': '../Vendor/foundation/vendor/jquery.cookie/jquery.cookie.js',
                    '../build/js/jquery.min.js': '../Vendor/foundation/vendor/jquery/dist/jquery.js',
                    '../build/js/jquery.validate.min.js': '../Vendor/jquery-validation/dist/jquery.validate.js'
                }
            }
        },
        copy: {
            dist: {
                files: [
                    {
                        src: [
                            '../Vendor/chartjs/Chart.min.js',
                            '../Vendor/build/videojs/video-js.min.js'
                        ],
                        dest: '../build/js',
                        expand: true,
                        flatten: true,
                        filter:'isFile'
                    },
                ]
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-compass');

    grunt.registerTask('default', ['compass', 'uglify', 'copy']);
    grunt.registerTask('watch', ['default','watch']);
};
