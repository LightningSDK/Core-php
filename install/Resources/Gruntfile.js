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
                        // Core - Required
                        '../../Lightning/build/js-res/foundation.js',
                        // Form Validation
                        '../../Lightning/build/js-res/foundation.abide.js',
                        //'../../Lightning/build/js-res/foundation.accordion.js',
                        //'../../Lightning/build/js-res/foundation.alert.js',
                        // Lightbox / image display:
                        //'../../Lightning/build/js-res/foundation.clearing.js',
                        //'../../Lightning/build/js-res/foundation.dropdown.js',
                        '../../Lightning/build/js-res/foundation.equalizer.js',
                        // For mobile optimization of images
                        //'../../Lightning/build/js-res/foundation.interchange.js',
                        //'../../Lightning/build/js-res/foundation.joyride.js',
                        //'../../Lightning/build/js-res/foundation.magellan.js',
                        // Side menu
                        //'../../Lightning/build/js-res/foundation.offcanvas.js',
                        //'../../Lightning/build/js-res/foundation.orbit.js',
                        // Modal
                        '../../Lightning/build/js-res/foundation.reveal.js',
                        //'../../Lightning/build/js-res/foundation.slider.js',
                        // Multi Tab Navigation
                        //'../../Lightning/build/js-res/foundation.tab.js',
                        //'../../Lightning/build/js-res/foundation.tooltip.js',
                        // Nav menu
                        '../../Lightning/build/js-res/foundation.topbar.js',
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
                        cwd: '../../Lightning/build/js',
                        src: '**',
                        dest: '../../js/',
                        expand: true
                    },
                    {
                        cwd: '../../Lightning/build/css',
                        src: '**',
                        dest: '../../css/',
                        expand: true
                    },
                    {
                        cwd: '../../Lightning/build/fonts',
                        src: '**',
                        dest: '../../fonts/',
                        expand: true
                    },
                    {
                        cwd: '../../Lightning/build/swf',
                        src: '**',
                        dest: '../../swf/',
                        expand: true
                    },
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
