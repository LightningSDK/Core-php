lightning.jsoneditor = {
    init: function() {
        for (var i in lightning.vars.jsoneditor) {
            var container = document.getElementById(i);
            var options = {};
            if (lightning.vars.jsoneditor[i].hasOwnProperty('options')) {
                options = lightning.vars.jsoneditor[i].options;
            }
            if (!options.hasOwnProperty('mode')) {
                options.mode = 'tree';
            }
            lightning.vars.jsoneditor[i].editor = new JSONEditor(container, options);
            lightning.vars.jsoneditor[i].editor.set(lightning.vars.jsoneditor[i].json)
        }
    }
};
