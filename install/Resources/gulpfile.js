// include gulp
var gulp = require("gulp");

var lightningFiles = "vendor/lightningsdk/core/lightning gulp";

var lightning = require("./vendor/lightningsdk/core/js/gulp-lightning.js");

// This will build any files described in the config file.
var execSync = require("child_process").execSync;

gulp.task("install", function(done) {
    var config = JSON.parse(execSync(lightningFiles).toString());
    lightning.install(done, config);
    done();
});

gulp.task("build-lightning", function(done) {
    var config = JSON.parse(execSync(lightningFiles).toString());
    lightning.compile(done, config);
    done();
});

gulp.task("watch-lightning", function(done) {
    var config = JSON.parse(execSync(lightningFiles).toString());
    lightning.watch(done, config);
    done();
});

gulp.task("build", gulp.series("build-lightning"));
gulp.task("default", gulp.series("build-lightning", "watch-lightning"));
