(function(){
    var self = lightning.widget = {

        id: null,
        container: null,

        initIframe: function(id) {
            window.onmessage = function(e) {
                if (typeof e.data == 'object' && e.data.hasOwnProperty('height')) {
                    if (e.data.animate) {
                        $('#' + id).animate({'height': e.data.height});
                    } else {
                        $('#' + id).height(e.data.height);
                    }
                }
            }
        },

        initBody: function(id) {
            self.id = id;
            self.container = $('#widget-body');
            self.resize();
        },

        resize: function(animate) {
            if (typeof animate == undefined) {
                animate = false
            }
            window.parent.postMessage({
                'height': self.container.height(),
                'animate': animate
            }, '*');
        }
    };
})();
