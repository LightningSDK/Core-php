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
        var renderer = lightning.vars.chart[id].renderer;
        window.myLine = new Chart(ctx)[renderer](data, {
            responsive: true,
            datasetFill: false
        });
    },

    updateTotals: function(id, data) {
        var container = $('#chart_totals_' + id);
        if (container.length == 1) {
            container.empty();
            if (data.datasets) {
                for (var i in data.datasets) {
                    var sum = 0;
                    for (var j in data.datasets[i].data) {
                        sum += parseInt(data.datasets[i].data[j]);
                    }
                    if (lightning.vars.chart[id].params.number_format && lightning.vars.chart[id].params.number_format == 'money') {
                        sum = '$' + sum.toFixed(2);
                    }
                    container.append($('<li>' + (data.datasets[i].label ? data.datasets[i].label : i) + ': ' + sum + '</li>'));
                }
            } else {
                var total = 0;
                for (var i in data) {
                    total += data[i].value;
                }
                container.append($('<li>Total: ' + total + '</li>'));
            }
        }
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
                    params[i] = $('#chart_controls_' + id + ' #' + param.source).val();;
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
        data.id = id;
        $.ajax({
            type: 'GET',
            url: lightning.vars.chart[id].url,
            dataType: 'JSON',
            data: data,
            success: function(result_data){
                self.drawData(id, result_data.data);
                self.updateTotals(id, result_data.data);
            }
        });
    },

    init: function() {
        var self = this;
        $('.chart_controls input, .chart_controls select').change(function(e){
            var id = $(e.target).closest('.chart_controls').attr('id').replace('chart_controls_', '');
            self.updateStats(id);
        });
    }
};
