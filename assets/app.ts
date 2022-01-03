import * as Highcharts from 'highcharts/highstock';
import {Tab} from "bootstrap"
import './styles/app.scss';
import {deleteCookie, setCookie} from "./utils";
import {StockChart} from "./charts";

window.document.addEventListener("DOMContentLoaded", function() {
    let charts: Array<StockChart> = new Array<StockChart>();

    // Action bar buttons
    new RefreshButton(window.document.querySelector('#refreshButton'));
    new PrivacyButton(window.document.querySelector('#privacyButton'));

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

class RefreshButton {
    constructor(button: HTMLElement) {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }
        if (button.disabled) {
            return;
        }

        button.addEventListener('click', this.onClick.bind(this))
    }

    private onClick(evt: MouseEvent) {
        //TODO
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
