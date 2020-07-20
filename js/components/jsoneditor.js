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
            options.ace = ace;
            options.modes = ['code', 'tree'];
            lightning.vars.jsoneditor[i].editor = new JSONEditor(container, options);
            lightning.vars.jsoneditor[i].editor.set(lightning.vars.jsoneditor[i].json)
        }

        // Make sure the json data is saved to the field before saving.
        $('.jsoneditor_presave').closest('form').submit(function(){
            $(this).find('.jsoneditor_presave').each(function(){
                var field = $(this);
                var id = $(this).prop('id').replace(/_data$/, '');
                field.val(JSON.stringify(lightning.vars.jsoneditor[id].editor.get()));
            });
        });
    }
};
