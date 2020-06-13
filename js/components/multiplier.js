lightning.multiplier = {
    init: function() {
        var self = this;
        $('.multiplier').each(function() {
            $(this).on('click', '.multiplier_add', null, function(e) {self.addOption(e.target)});
            $(this).on('click', '.multiplier_remove', null, function(e) {self.removeOption(e.target)});
            $(this).append('<input class="multiplier_count" value="0" type="hidden" />');
            $(this).find('.template').hide();
            self.addOption(this);
        });
    },

    addOption: function(target) {
        var container = $(target).closest('.multiplier');
        var template = container.find('.template').html();
        var count = container.find('.multiplier_count').val();
        var newElement = $('<div class="item"></div>').append(template.replace(/%/g, count + 1));
        container.find('.multiplier_count').val(count + 1);
        container.append(newElement);
        this.adjustControls(container);
    },

    removeOption: function(target) {
        $(target).closest('.item').remove();
        this.adjustControls($(target).closest('.multiplier'));
    },

    adjustControls: function(container) {
        container.find('.multiplier_add').hide();
        container.find('.multiplier_add').last().show();
        container.find('.multiplier_remove').show();
    }
};
