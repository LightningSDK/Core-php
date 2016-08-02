var gulp = require('gulp');

var compass = require('gulp-compass');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');
var rename = require('gulp-rename');

gulp.task('compass', function() {
    gulp.src('sass/*.scss')
        .pipe(compass({
            css: '../build/css',
            sass: 'sass',
            image: '../build/images',
            import_path: [
                '../Vendor/compass/frameworks/compass/stylesheets',
                '../Vendor/foundation/scss'
            ]
        }))
        .pipe(gulp.dest('../build/css'));
});

gulp.task('uglify', function() {
    gulp.src([
        'init.js',
        'js/*.js'
    ])
        .pipe(uglify())
        .pipe(concat('lightning.min.js'))
        .pipe(gulp.dest('../build/js-res'));

    gulp.src('../Vendor/foundation/vendor/modernizr/modernizr.js')
        .pipe(uglify())
        .pipe(rename('modernizr.min.js'))
        .pipe(gulp.dest('../build/js-res'));

    gulp.src('../Vendor/foundation/vendor/jquery-placeholder/jquery.placeholder.js')
        .pipe(uglify())
        .pipe(rename('placeholder.min.js'))
        .pipe(gulp.dest('../build/js-res'));

    gulp.src('../Vendor/foundation/vendor/fastclick/lib/fastclick.js')
        .pipe(uglify())
        .pipe(rename('fastclick.min.js'))
        .pipe(gulp.dest('../build/js-res'));
});

gulp.task('copy', function(){
    gulp.src(['../Vendor/foundation/js/foundation/*.js'])
        .pipe(gulp.dest('../build/js-res'));

    gulp.src([
        '../Vendor/chartjs/Chart.min.js',
        '../Vendor/videojs/dist/video.min.js',
        '../Vendor/jsoneditor/dist/jsoneditor.min.js',
    ])
        .pipe(gulp.dest('../build/js'));

    gulp.src(['../Vendor/jquery/dist/jquery.js'])
        .pipe(gulp.dest('../build/js-res'));

    gulp.src([
        '../Vendor/Font-Awesome/css/font-awesome.min.css',
        '../Vendor/videojs/dist/video-js.min.css',
    ])
        .pipe(gulp.dest('../build/css'));

    gulp.src([
        '../Vendor/videojs/dist/font/*',
    ])
        .pipe(gulp.dest('../build/css/font'));

    gulp.src(['../Vendor/Font-Awesome/fonts/*'])
        .pipe(gulp.dest('../build/fonts'));

    gulp.src(['../Vendor/videojs/dist/video-js.swf'])
        .pipe(gulp.dest('../build/swf'));
});

gulp.task('default',  ['compass', 'uglify', 'copy'], function(){});
