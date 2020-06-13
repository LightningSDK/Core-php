var gulp = require("gulp");
var fs = require("fs");
var log = require("fancy-log");
var minifyCSS = require("gulp-minify-css");
var uglify = require("gulp-uglify-es").default;
var concat = require("gulp-concat");
var gzip = require("gulp-gzip");
var sass = require("gulp-sass");
var header = require('gulp-header');
var cleanCSS = require("gulp-clean-css");
var gulpif = require('gulp-if');

module.exports = {
    compile: function(done, config){
        compile(done, config);

        done();
    },
    install: function(done, config){
        if (!config.hasOwnProperty('copy')) {
            log('Nothing to install')
        } else {
            copyFiles(done, config.copy);
        }

        done();
    }
}

function compile(done, config){
    var types = ["js", "css"];
    for (var type in types) {
        var dest_files = getDestFiles(config[types[type]], types[type]);

        for (var i in dest_files) {
            var sorted = sortSourceFiles(dest_files[i]);
            // An error occurred
            if (typeof sorted === "string") {
                return done(sorted);
            }

            log("writing to : " + i);
            log(sorted);

            // Write the files
            var g = gulp.src(sorted);
            switch (types[type]) {
                case "js":
                    g.pipe(uglify());
                    break;
                case "css":
                    var sassConfig = getSassConfig(config)
                    g = g
                        .pipe(gulpif(sassOnly, header(sassConfig.header)))
                        .pipe(sass({
                            includePaths: sassConfig.includes
                        }).on('error', sass.logError))
                        .pipe(minifyCSS())
                        .pipe(cleanCSS({compatibility: "ie8"}))
                    break;
            }

            g
                .pipe(concat(i))
                .pipe(gulp.dest(types[type]))
                .pipe(gzip())
                .pipe(gulp.dest(types[type]));
        }
    }
};

function getDestFiles(modules, type) {
    var dest_files = {};
    for (var module_name in modules) {
        for (var source_file in modules[module_name]) {
            // Normalize destination files
            var dest_file = [];
            if (typeof modules[module_name][source_file] === "string") {
                dest_file.dest = modules[module_name][source_file];
            } else {
                dest_file = modules[module_name][source_file];
                if (!dest_file.hasOwnProperty("dest")) {
                    done("no destination set for script " + source_file + " in module " + module_name);
                }
            }
            dest_file.module = module_name;
            dest_file.source = getPath(module_name, source_file, type);

            // Add the dest file
            if (!dest_files[dest_file.dest]) {
                dest_files[dest_file.dest] = [];
            }
            dest_files[dest_file.dest][dest_file.source] = dest_file;
        }
    }

    return dest_files;
}

function sassOnly (file) {
    return file.path.match(/.s[ac]ss$/);
}

function getSassConfig(config) {
    var sassConfig = config.hasOwnProperty("sass") ? config.sass : {};
    if (!sassConfig.hasOwnProperty("includes")) {
        sassConfig.includes = [];
    }
    if (!sassConfig.hasOwnProperty("vars")) {
        sassConfig.vars = [];
    }
    sassConfig.header = '';
    for (var v in sassConfig.vars) {
        sassConfig.header += v + ": '" + sassConfig.vars[v] + "';\n";
    }

    return sassConfig;
}

function getPath(module, file, type) {
    if (module === "Source") {
        return "Source/Resources/" + type + "/" + file;
    }
    var paths = [
        "Modules/" + module + "/" + type + "/" + file,
        "vendor/" + module + "/" + type + "/" + file,
        "vendor/" + module + "/" + file,
        file,
    ];
    var files = [];
    for (var i in paths) {
        var glob = require("glob")
        var exists = false;
        files = glob.sync(paths[i], {"allowEmpty":true});
        if (files.length > 0) {
            return paths[i];
        }
    }
    return file;
}

function copyFiles(done, files) {
    for (module in files) {
        for (src in files[module]) {
            var file = getPath(module, src, '');
            log("Copying:");
            log(file);
            log(files[module][src]);
            gulp.src(file)
                .pipe(gulp.dest(files[module][src]))
        }
    }
}

function sortSourceFiles(sources) {
    var iterationLength = 0;
    var sortedSources = [];
    var modules = [];
    var error;
    var sourceLength = 0;
    for (var i in sources) {
        sourceLength++;
        // normalize requirements to array
        if (!sources[i].hasOwnProperty("requires_module")) {
            sources[i].requires_module = [];
        } else if (typeof sources[i].requires_module === "string") {
            sources[i].requires_module = [sources[i].requires_module];
        }
    }
    do {
        iterationLength = sortedSources.length;
        source_check:
            for (i in sources) {
                if (sources[i].hasOwnProperty("included")) {
                    // This one is already included
                    continue;
                }

                // check for requirements
                for (var j in sources[i].requires_module) {
                    if (!modules.hasOwnProperty(sources[i].requires_module[j])) {
                        // A required module has not been added yet
                        error = "Could not load source " + i + " in module " + sources[i].module + " because dependency was not met: " + sources[i].requires_module[j];
                        continue source_check;
                    }
                }

                // All requirements have been met and we can add this source now
                sortedSources.push(i);
                modules[sources[i].module] = true;
                sources[i].included = true;
            }
    } while (iterationLength < sortedSources.length && sortedSources.length < sourceLength);

    // If we didn't load all the sources, then return the last error
    if (sortedSources.length < sourceLength) {
        return error;
    }
    return sortedSources;
}
