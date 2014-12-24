lightning.stats = {
    loadData: function() {
        var requestData = {
            sets: []
        };
        $('#stats_controls select').not('#tracker_\\%').each(function(){
            var id = $(this).attr('id').replace('tracker_', '');
            requestData.sets.push(
                {
                    tracker: $('#tracker_' + id).val(),
                    sub_id: $('#sub_id_' + id).val(),
                }
            );
        });

        this.getTrackerStats(requestData, this.updateStats)
    },

    drawData: function(id, data) {
        var ctx = document.getElementById(id).getContext("2d");
        window.myLine = new Chart(ctx).Line(data, {
            responsive: true,
            datasetFill: false
        });
    },

    getTrackerStats: function(id, data, callback) {
        data.action = 'trackerStats';
        $.ajax({
            type: 'GET',
            url: '/admin/tracker',
            dataType: 'JSON',
            data: data,
            success: callback
        });
    },

    getParameters: function(id) {
        if (!lightning.vars.chart[id].params) {
            return {};
        } else {
            var params = {};
            for (var i in lightning.vars.chart[id].params) {
                var param = lightning.vars.chart[id].params[i];
                if (param.source) {
                    params[i] = $('#' + param.source).val();
                } else if (param.value) {
                    params[i] = param.value;
                }
            }
            return params;
        }
    },

    updateStats: function(id) {
        var self = this;
        var data = this.getParameters(id);
        data.action = 'get-data';
        $.ajax({
            type: 'GET',
            url: lightning.vars.chart[id].url,
            dataType: 'JSON',
            data: data,
            success: function(result_data){ self.drawData(id, result_data) }
        });
    }
};
