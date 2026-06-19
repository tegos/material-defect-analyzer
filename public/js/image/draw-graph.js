let animationDone = false;
let pendingCharts = [];

function flushPendingCharts() {
    if (animationDone) return;
    animationDone = true;
    pendingCharts.forEach(fn => fn());
    pendingCharts = [];
}

function scheduleChart(fn) {
    if (animationDone) {
        fn();
    } else {
        pendingCharts.push(fn);
    }
}

$(document).ready(function () {
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}
    });

    $('#intro .inner').one('transitionend', flushPendingCharts);
    setTimeout(flushPendingCharts, 1200);

    let graph_columns = $('#table_intensity .column_graph');
    graph_columns.each(function () {
        let t = $(this);
        let position = t.data('position');
        let element_id = 'graph_intensity_' + position;

        $.ajax({
            url: `/ajax/intensity/${imageId}/${position}`,
            dataType: 'json',
            success: function (data) {
                scheduleChart(() => initChart(element_id, data));
            }
        });

    });

    // group chart

    let groupChart = $('#groupChart .groupChart');
    groupChart.each(function () {
        let t = $(this);
        let imageKeys = t.data('image');
        let element_id = t.attr('id');

        $.ajax({
            url: '/ajax/chart',
            type: 'post',
            dataType: 'json',
            data: {
                featureDataOfImages,
                imageKeys
            },
            success: function (data) {
                scheduleChart(() => initChartGroup(element_id, data));
            }
        });

    });
});

let initChart = function (element_id = '', series_data = []) {

    Highcharts.chart(element_id, {

        title: {
            text: chartTitle
        },

        subtitle: {
            text: chartSubTitle
        },

        yAxis: {
            max: 255,
            min: 0,
            title: {
                text: yFeatureText
            },
            alignTicks: false,
            endOnTick: false,
        },

        series: [
            {
                name: chartTitle,
                data: series_data
            }
        ],
        chart: {
            type: 'line',
            width: null,
            alignTicks: false,
            events: {
                load: function () {
                    setTimeout(() => {
                        $('svg').each(function () {
                            $(this).find('.highcharts-credits').last().remove();
                        });
                    }, 1000);
                }
            }
        }

    });

};

let initChartGroup = function (element_id = '', series_data = [], min = 50, max = 250) {
    if (series_data[0].min) {
        min = series_data[0].min;
    }

    if (series_data[0].max) {
        max = series_data[0].max;
    }

    Highcharts.chart(element_id, {
        title: {
            text: chartTitle
        },
        subtitle: {
            text: chartSubTitle
        },
        yAxis: {
            max: max,
            min: min,
            title: {
                text: yFeatureText
            },
            alignTicks: false,
            endOnTick: false,
        },

        series: series_data,
        chart: {
            type: 'line',
            width: null,
            alignTicks: false,
            events: {
                load: function () {
                    setTimeout(() => {
                        $('svg').each(function () {
                            $(this).find('.highcharts-credits').last().remove();
                        });
                    }, 1000);
                }
            }
        }

    });

};
