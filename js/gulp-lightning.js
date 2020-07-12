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
var watch = require('gulp-watch');
var shell = require('gulp-shell');

module.exports = {
    install: function(done, config){
        if (!config.hasOwnProperty('copy')) {
            log('No files to copy')
        } else {
            copyFiles(done, config.copy);
        }

        if (!config.hasOwnProperty('npm')) {
            log('No npm packages to install')
        } else {
            installNPM(done, config.npm);
        }
    },
    compile: function(done, config){
        var manifest = buildManifest(done, config);
        compile(done, manifest, config);
    },
    watch: function(done, config) {
        var manifest = buildManifest(done, config);
        watchFiles(done, manifest, config);
    }
};

function watchFiles(done, manifest, config) {
    for (var i in manifest) {
        log(i);
        (function(file){
            watch(manifest[i].sorted, function(){
                compileFile(done, file, manifest[file], config);
            })
        })(i);
    }
}

function compile(done, manifest, config) {
    for (var i in manifest) {
        compileFile(done, i, manifest[i], config);
    }
}

function compileFile(done, outputFile, compileInfo, config) {
    // An error occurred
    if (typeof compileInfo.sorted === "string") {
        return done(compileInfo.sorted);
    }

    log("writing to : " + outputFile);
    log(compileInfo.sorted);

    // Write the files
    var g = gulp.src(compileInfo.sorted);
    switch (compileInfo.type) {
        case "js":
            g = g.pipe(uglify());
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

    // If the file ends in .js or .css then we will combine everything
    // otherwise this is meant to write multiple files to a directory.
    if (outputFile.match(/\.(css|js)$/)) {
        g = g.pipe(concat(outputFile))
    }
    g
        .pipe(gulp.dest(compileInfo.type))
        .pipe(gzip())
        .pipe(gulp.dest(compileInfo.type));
}

/**
 * Builds manifest of all files
 * @param done
 * @param config
 * @returns {
 *     dest_file_path: {
 *         sources: [ "file1":{
 *             module: "lightningsdk/core",
 *             requires_module: ["other/module"],
 *         }],
 *         type: "js",
 *         "sorted": [
 *             "file1",
 *             "file2",
 *         ],
 *     }
 * }
 */
function buildManifest(done, config) {
    var manifest = {};
    var types = ["js", "css"];
    for (var t in types) {
        var type = types[t];
        for (var module_name in config[type]) {
            for (var source_file in config[type][module_name]) {
                // Normalize destination files
                var dest_file = [];
                if (typeof config[type][module_name][source_file] === "string") {
                    dest_file.dest = config[type][module_name][source_file];
                } else {
                    dest_file = config[type][module_name][source_file];
                    if (!dest_file.hasOwnProperty("dest")) {
                        done("no destination set for script " + source_file + " in module " + module_name);
                    }
                }
                dest_file.module = module_name;
                dest_file.source = getPath(done, module_name, source_file, type);

                // Add the dest file
                if (!manifest[dest_file.dest]) {
                    manifest[dest_file.dest] = {"sources":[], "type": type};
                }
                manifest[dest_file.dest].sources[dest_file.source] = dest_file;
            }
        }
    }

    for (var i in manifest) {
        manifest[i].sorted = sortSourceFiles(manifest[i]);
    }

    return manifest;
}

function sassOnly (file) {
    return file.path.match(/.s[ac]ss$/);
}

function getSassConfig(config) {
    var sassConfig = config.hasOwnProperty("sass") ? config.sass : {};

    if (sassConfig.hasOwnProperty("includes")) {
        sassConfig.includes = Object.values(sassConfig.includes);
    } else {
        sassConfig.vars = [];
    }

    sassConfig.header = '';
    for (var v in sassConfig.vars) {
        sassConfig.header += v + ": '" + sassConfig.vars[v] + "';\n";
    }

    return sassConfig;
}

function getPath(done, module, file, type) {
    if (module === "Source") {
        return "Source/Resources/" + file;
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
    log("File not found: " + file);
    log(paths);
    done("Error");
}

function copyFiles(done, files) {
    for (module in files) {
        for (src in files[module]) {
            var file = getPath(done, module, src, '');
            log("Copying:");
            log(file);
            log(files[module][src]);
            gulp.src(file)
                .pipe(gulp.dest(files[module][src]))
        }
    }
}

function installNPM(done, packages) {
    log("Installing:");
    log(packages);
    gulp.src(".").pipe(shell(['npm install ' + packages.join(" ")]))
}

function sortSourceFiles(dest) {
    var iterationLength = 0;
    var sortedSources = [];
    var modules = [];
    var error;
    var sourceLength = 0;
    var sources = dest.sources;
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
