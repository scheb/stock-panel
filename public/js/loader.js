function setPrivacy(isEnabled) {
    $.cookie('privacy', isEnabled ? 1 : 0, { expires: 360 });
    if (isEnabled) {
        $(".privacy").hide();
        $(".btn-privacy .glyphicon").addClass("glyphicon-eye-close").removeClass("glyphicon-eye-open");
    }
    else {
        $(".privacy").show();
        $(".btn-privacy .glyphicon").addClass("glyphicon-eye-open").removeClass("glyphicon-eye-close");
    }
}

function togglePrivacy() {
    if ($.cookie('privacy') == "1") {
        setPrivacy(false);
    }
    else {
        setPrivacy(true);
    }
}

function getChartDataUrl(stockId, period) {
    return '/charts/' + stockId + '/' + period  + '.json?callback=?';
}

function initHighchart(stockId, data) {
    var highcharts = Highcharts.stockChart("chart-" + stockId, {
        chart: {
            zoomType: false
        },
        navigator: {
            enabled: false
        },
        scrollbar: {
            enabled: false
        },
        rangeSelector: {
            enabled: false
        },
        credits: {
            enabled: false
        },
        xAxis: {
            crosshair: true,
            type: "datetime",
            minRange: 3600 * 1000 // one hour
        },
        yAxis: [{
            crosshair: true,
            labels: {
                align: 'right',
                x: -3
            },
            title: {
                text: false
            },
            height: '100%',
            lineWidth: 2,
            resize: {
                enabled: true
            }
        }, {
            labels: {
                align: 'right',
                x: -3
            },
            title: {
                text: false
            },
            top: '70%',
            height: '30%',
            offset: 0,
            lineWidth: 2
        }],
        tooltip: {
            split: true
        },
        plotOptions: {
            series: {
                connectNulls: true
            }
        },
        series: [{
            type: 'area',
            threshold: null,
            name: 'Price',
            data: data.price,
            dataGrouping: {
                enabled: false,
            },
            tooltip: {
                valueDecimals: 2
            }
        }, {
            type: 'column',
            name: 'Volume',
            data: data.volume,
            yAxis: 1,
            groupPadding: 0,
            minPointWidth: 3,
            color: "rgba(0,0,0,0.5)",
            dataGrouping: {
                enabled: false
            }
        }]
    });
    if (data === null) {
        highcharts.showLoading('No data available');
    }
}

function initStockChart(stockId) {
    Highcharts.setOptions({
        lang: {
            decimalPoint: '.',
            thousandsSep: ','
        }
    });
    $.ajax({
        url: getChartDataUrl(stockId, "1d"),
        dataType: 'json',
        success: function (data) {
            initHighchart(stockId, data);
        },
        error: function () {
            initHighchart(stockId, null);
        }
    });
}

function changeStockChartsPeriod(period) {
    $('#stock-charts .chart').each(function () {
        var chart = $(this);
        var highcharts = chart.highcharts();
        var stockId = chart.attr('data-stock-id');
        if (highcharts) {
            highcharts.showLoading('Loading data from server...');
            $.ajax({
                url: getChartDataUrl(stockId, period),
                dataType: 'json',
                success: function (data) {
                    highcharts.series[0].setData(data.price);
                    highcharts.series[1].setData(data.volume);
                    highcharts.hideLoading();
                },
                error: function () {
                    highcharts.series[0].setData([]);
                    highcharts.series[1].setData([]);
                    highcharts.showLoading('No data available');
                }
            });
        }
    });
}

$(document).ready(function() {
    //External links
    $('a[rel*=external]').click( function() {
        window.open(this.href);
        return false;
    });

    $(".btn-privacy").click(togglePrivacy);
    setPrivacy($.cookie('privacy') == "1");

    // Charts overview only
    $('#period-tabs a').click(function () {
        var period = $(this).attr('data-period');
        changeStockChartsPeriod(period);
    });
});
