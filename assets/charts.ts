import {getRequest, RequestResult} from "./request";
import * as Highcharts from "highcharts/highstock";

interface ChartData {
    volume: any,
    price: any,
}

export class StockChart {
    private chartContainer: HTMLElement;
    private stockId: number;
    private highStockChart: any = null;

    constructor(chartContainer: HTMLElement) {
        this.chartContainer = chartContainer;
        this.stockId = chartContainer.dataset['stockId'] as unknown as number;
        getRequest(this.getChartDataUrl(this.stockId, "1d"), {}, this.onDataLoaded.bind(this));
    }

    public changePeriod(period: string) {
        if (null === this.highStockChart) {
            return;
        }

        this.highStockChart.showLoading('Loading data from server...');
        getRequest(this.getChartDataUrl(this.stockId, period), {}, this.onDataRefreshLoaded.bind(this));
    }

    private onDataLoaded(response: RequestResult) {
        if (response.status === 200) {
            const data = response.json<ChartData>();
            this.initHighStock(this.stockId, data);
        } else {
            this.initHighStock(this.stockId, null);
        }
    }

    private onDataRefreshLoaded(response: RequestResult) {
        if (response.status === 200) {
            const data = response.json<ChartData>();
            this.highStockChart.series[0].setData(data.price);
            this.highStockChart.series[1].setData(data.volume);
            this.highStockChart.hideLoading();
        } else {
            this.highStockChart.series[0].setData([]);
            this.highStockChart.series[1].setData([]);
            this.highStockChart.showLoading('No data available');
        }
    }

    private getChartDataUrl(stockId: number, period: string): string {
        return '/charts/' + stockId + '/' + period  + '.json';
    }

    private initHighStock(stockId: number, data: null|ChartData) {
        this.highStockChart = (Highcharts as any).stockChart("chart-" + stockId, {
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
        if (null === data) {
            this.highStockChart.showLoading('No data available');
        }
    }
}
