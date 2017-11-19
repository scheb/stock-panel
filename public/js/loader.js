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
            type: "datetime",
            minRange: 3600 * 1000 // one hour
        },
        yAxis: {
            floor: 0
        },
        plotOptions: {
            series: {
                connectNulls: true
            }
        },
        series: [{
            name: 'Price',
            data: data,
            dataGrouping: {
                enabled: false
            },
            tooltip: {
                valueDecimals: 2
            }
        }]
    });
    if (data === null) {
        highcharts.showLoading('No data available');
    }
}

function initStockChart(stockId) {
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
                    highcharts.series[0].setData(data);
                    highcharts.hideLoading();
                },
                error: function () {
                    highcharts.series[0].setData([]);
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
