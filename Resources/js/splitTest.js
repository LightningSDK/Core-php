lightning.splitTest = {
    init: function() {
        if (lightning.vars.hasOwnProperty('splitTest')) {
            for (var i in lightning.vars.splitTest) {

                ga('send', {
                    hitType: 'event',
                    eventCategory: i,
                    eventAction: lightning.vars.splitTest[i],
                    eventLabel: 'Split Test'
                });
            }
        }
    }
};
