// include gulp
var gulp = require('gulp');

var compass = require('gulp-compass');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');
var rename = require("gulp-rename");
var cleanCSS = require('gulp-clean-css');
var gzip = require('gulp-gzip');

gulp.task('compass', function() {
    var compass_settings = {
        css: '../../css',
        sass: 'sass',
        image: '../../images',
        import_path: [
            '../../Lightning/Vendor/foundation/scss',
            '../../Lightning/Vendor/compass/frameworks/compass/stylesheets',
            '../../Lightning/Resources/sass',
            '../../Lightning/Resources/node_modules/grunt-sass/node_modules/node-sass/test/fixtures/spec/spec/libsass/bourbon/lib',
            '../../Modules',
            '../../Lightning/build/scss',
            '../../Lightning/Vendor/Font-Awesome/scss',
        ],
        font: '../../fonts',
    };

    gulp.src('sass/**/*.scss')
        .pipe(compass(compass_settings))
        .pipe(cleanCSS({compatibility: 'ie8'}))
        .pipe(gulp.dest('../../css'))
        .pipe(gzip())
        .pipe(gulp.dest('../../css'));
});

gulp.task('uglify', function(){
    gulp.src([
        '../../Lightning/build/js-res/fastclick.min.js',
        '../../Lightning/build/js-res/jquery.js',
        '../../Lightning/build/js-res/placeholder.min.js',
        '../../Lightning/build/js-res/modernizr.min.js',
        '../../Lightning/build/js-res/foundation.js',
        // Foundation utilities
        '../../Lightning/build/js-res/foundation.util.*.js',
        '../../Lightning/build/js-res/foundation.abide.js',
        //'../../Lightning/build/js-res/foundation.accordion.js',
        //'../../Lightning/build/js-res/foundation.alert.js',
        // Lightbox / image display:
        //'../../Lightning/build/js-res/foundation.clearing.js',
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
        //'../../Lightning/build/js-res/foundation.tabs.js',
        //'../../Lightning/build/js-res/foundation.tooltip.js',
        // Nav menu
        '../../Lightning/build/js-res/foundation.topbar.js',
        '../../Lightning/build/js-res/lightning.min.js'
    ])
        .pipe(uglify())
        .pipe(concat('lightning.min.js'))
        .pipe(gulp.dest('../../js'))
        .pipe(gzip())
        .pipe(gulp.dest('../../js'));
    gulp.src('js/ckeditor_config.js')
        .pipe(uglify())
        .pipe(rename('config.js'))
        .pipe(gulp.dest('../../js/ckeditor'))
        .pipe(gzip())
        .pipe(gulp.dest('../../js/ckeditor'));
    gulp.src('js/site/*.js')
        .pipe(uglify())
        .pipe(concat('site.min.js'))
        .pipe(gulp.dest('../../js'))
        .pipe(gzip())
        .pipe(gulp.dest('../../js'));
});

var execSync = require('child_process').execSync;
gulp.task('uglify-modules', function(){
    var config = JSON.parse(execSync('../../Lightning/lightning gulp').toString());
    var dest_files = {};
    for (var module_name in config.js) {
        for (var source_file in config.js[module_name]) {
            var dest_file = config.js[module_name][source_file];
            if (!dest_files[dest_file]) {
                dest_files[dest_file] = [];
            }
            var module_path = (module_name == 'Source' ? ('js/') : ('../../Modules/' + module_name + '/js/'));
            dest_files[dest_file].push(module_path + source_file);
        }
    }
    console.log(dest_files);
    for (var i in dest_files) {
        gulp.src(dest_files[i])
            .pipe(uglify())
            .pipe(concat(i))
            .pipe(gulp.dest('../../js'))
            .pipe(gzip())
            .pipe(gulp.dest('../../js'));
    }
});

gulp.task('copy', function(){
    gulp.src('../../Lightning/build/js/**')
        .pipe(gulp.dest('../../js'))
        .pipe(gzip())
        .pipe(gulp.dest('../../js'));
    gulp.src('../../Lightning/build/css/**')
        .pipe(gulp.dest('../../css'))
        .pipe(gzip())
        .pipe(gulp.dest('../../css'));
    gulp.src('../../Lightning/build/fonts/**')
        .pipe(gulp.dest('../../fonts'))
        .pipe(gzip())
        .pipe(gulp.dest('../../fonts'));
    gulp.src('../Vendor/slick/slick/fonts/*')
        .pipe(gulp.dest('../../fonts'))
        .pipe(gzip())
        .pipe(gulp.dest('../../fonts'));
    gulp.src('../../Lightning/build/swf/**')
        .pipe(gulp.dest('../../swf'))
        .pipe(gzip())
        .pipe(gulp.dest('../../swf'));
    gulp.src('../Vendor/slick/slick/slick.min.js')
        .pipe(gulp.dest('../../js'))
        .pipe(gzip())
        .pipe(gulp.dest('../../js'));
});

gulp.task('watch', function(){
    gulp.watch([
        '../../Lightning/Resources/sass/**',
        'sass/**'
    ], ['compass']);
    gulp.watch([
        '../../Lightning/build/js/**',
        '../../Lightning/build/js-res/**',
        'js/**'
    ], ['uglify']);
    gulp.watch([
        '../../Modules/**/*.js'
    ], ['uglify-modules']);
});

gulp.task('default',  ['build', 'watch'], function(){});
gulp.task('build',  ['compass', 'uglify', 'copy', 'uglify-modules'], function(){});
