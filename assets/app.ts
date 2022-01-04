import * as Highcharts from 'highcharts/highstock';
import {Tab} from "bootstrap"
import './styles/app.scss';
import {deleteCookie, setCookie} from "./utils";
import {StockChart} from "./charts";
import {getRequest, RequestResult} from "./request";

window.document.addEventListener("DOMContentLoaded", function() {
    let charts: Array<StockChart> = new Array<StockChart>();

    // Refresh button
    var stockTable = window.document.getElementById('stockTable');
    if (stockTable !== null) {
        new RefreshButton(
            window.document.getElementById('refreshButton'),
            window.document.getElementById('stockTable')
        );
    }

    // Privacy button
    new PrivacyButton(window.document.getElementById('privacyButton'));

    // Delete buttons
    initDeleteButtons();

    // Init tabs
    window.document.querySelectorAll('[data-bs-toggle="tab"]')
        .forEach((tabNode: Element) => {
            if (tabNode instanceof HTMLElement) {
                new Tab(tabNode);
                tabNode.addEventListener('shown.bs.tab', function (e) {
                    const period = tabNode.dataset['period'];
                    charts.forEach((chart: StockChart) => chart.changePeriod(period));
                });
            }
        });

    // Init charts
    window.document.querySelectorAll('[data-stock-id]')
        .forEach((chartContainer: Element) => {
            if (chartContainer instanceof HTMLElement) {
                charts.push(new StockChart(chartContainer));
            }
        });

    // Global highcharts config
    (Highcharts as any).setOptions({
        lang: {
            decimalPoint: ',',
            thousandsSep: '.'
        }
    });
});

function initDeleteButtons() {
    window.document.querySelectorAll('[data-delete-stock]')
        .forEach((deleteButton: Element) => {
            if (deleteButton instanceof HTMLButtonElement) {
                deleteButton.addEventListener('click', () => {
                    if (window.confirm('Aktie entfernen?')) {
                        window.location.href = '/delete/'+ deleteButton.dataset['deleteStock'];
                    }
                })
            }
        });
}

class RefreshButton {
    private readonly REFRESH_TIMEOUT = 5 * 60 * 1000; //Update every 5 minutes
    private button: HTMLButtonElement;
    private stockTable: HTMLElement;
    private updateTimeout: number = null;

    constructor(button: HTMLElement, stockTable: HTMLElement) {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }
        if (button.disabled) {
            return;
        }

        this.button = button;
        this.stockTable = stockTable;
        this.button.addEventListener('click', this.onClick.bind(this))
        this.startAutoRefresh();
    }

    private onClick(evt: MouseEvent) {
        this.haltAutoRefresh();
        this.updateView();
    }

    private updateView() {
        this.setLoading(true);
        getRequest('update', {},
            this.onViewUpdate.bind(this)
        );
    }

    private onViewUpdate(response: RequestResult) {
        if (response.status === 200) {
            this.stockTable.innerHTML = response.data;
            initDeleteButtons();
            this.setLoading(false);
            this.startAutoRefresh();
        }
    }

    private setLoading(isLoading: boolean) {
        this.button.disabled = isLoading;
        if (isLoading) {
            this.button.classList.add('loading');
            this.stockTable.classList.add('loading');
        } else {
            this.button.classList.remove('loading');
            this.stockTable.classList.remove('loading');
        }
    }

    private startAutoRefresh() {
        this.updateTimeout = window.setTimeout(this.updateView.bind(this), this.REFRESH_TIMEOUT);
    }

    private haltAutoRefresh() {
        if (null !== this.updateTimeout) {
            clearTimeout(this.updateTimeout);
            this.updateTimeout = null;
        }
    }
}

class PrivacyButton {
    private readonly PRIVACY_CSS_CLASS = 'privacyIsActive';
    private readonly COOKIE_NAME = 'privacyIsActive';
    private button: HTMLButtonElement;
    private bodyClasses: DOMTokenList;

    constructor(button: Element) {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        this.button = button;
        this.bodyClasses = window.document.body.classList;
        button.addEventListener('click', this.onClick.bind(this));
    }

    private onClick(evt: MouseEvent) {
        this.setPrivacyActive(!this.isPrivacyActive());
    }

    private isPrivacyActive(): boolean {
        return this.bodyClasses.contains(this.PRIVACY_CSS_CLASS);
    }

    private setPrivacyActive(isActive: boolean) {
        if (isActive) {
            this.bodyClasses.add(this.PRIVACY_CSS_CLASS);
            setCookie(this.COOKIE_NAME, '1');
        } else {
            this.bodyClasses.remove(this.PRIVACY_CSS_CLASS);
            deleteCookie(this.COOKIE_NAME);
        }
    }
}
