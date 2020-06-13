/**
 * Converts a string into a function reference.
 * @param name
 *   The function name.
 *
 * @returns {function}
 *   A references to the function.
 */
lightning.getMethodReference = function(name) {
    var split = name.split('.');
    var parent = window;
    for (var i in split) {
        parent = parent[split[i]];
    }
    return parent;
};
