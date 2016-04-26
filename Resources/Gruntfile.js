
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
                    '../build/js-res/lightning.min.js': ['init.js', 'js/*.js'],
                    '../build/js-res/modernizr.min.js': '../Vendor/foundation/vendor/modernizr/modernizr.js',
                    '../build/js-res/placeholder.min.js': '../Vendor/foundation/vendor/jquery-placeholder/jquery.placeholder.js',
                    '../build/js-res/fastclick.min.js': '../Vendor/foundation/vendor/fastclick/lib/fastclick.js',
                }
            }
        },
        copy: {
            dist: {
                files: [
                    {
                        src: [
                            '../Vendor/chartjs/Chart.min.js',
                            '../Vendor/videojs/build/temp/video.min.js',
                            '../Vendor/jsoneditor/dist/jsoneditor.min.js'
                        ],
                        dest: '../build/js',
                        expand: true,
                        flatten: true,
                        filter: 'isFile'
                    },
                    {
                        src: [
                            '../Vendor/foundation/js/foundation/*',
                            '../Vendor/jquery/dist/jquery.js'
                        ],
                        dest: '../build/js-res',
                        expand: true,
                        flatten: true,
                        filter: 'isFile'
                    },
                    {
                        src: [
                            '../Vendor/Font-Awesome/css/font-awesome.min.css',
                            '../Vendor/videojs/build/temp/video-js.min.css',
                        ],
                        dest: '../build/css',
                        expand: true,
                        flatten: true,
                        filter: 'isFile'
                    },
                    {
                        src: '../Vendor/Font-Awesome/fonts/*',
                        dest: '../build/fonts',
                        expand: true,
                        flatten: true,
                        filter: 'isFile'
                    },
                    {
                        src: '../Vendor/videojs/build/temp/video-js.swf',
                        dest: '../build/swf',
                        expand: true,
                        flatten: true,
                        filter: 'isFile'
                    }
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
