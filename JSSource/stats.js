lightning.stats = {
    loadData: function() {
        var ctx = document.getElementById("canvas").getContext("2d");

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

        this.getTrackerStats(
            requestData,
            function(data) {
                window.myLine = new Chart(ctx).Line(data, {
                    responsive: true,
                    datasetFill: false,
                });
            }
        )
    },

    getTrackerStats: function(data, callback) {
        data.action = 'trackerStats';
        $.ajax({
            type: 'GET',
            url: '/admin/tracker',
            dataType: 'JSON',
            data: data,
            success: callback
        });
    }
};
