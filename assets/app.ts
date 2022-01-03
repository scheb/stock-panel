import {Tab} from "bootstrap"
import './styles/app.scss';
import {deleteCookie, setCookie} from "./utils";

window.document.addEventListener("DOMContentLoaded", function() {
    // Init tabs
    window.document.querySelectorAll('[data-bs-toggle="tab"]')
        .forEach((tabNode: Element) => new Tab(tabNode));

    // Action bar buttons
    new RefreshButton(window.document.querySelector('#refreshButton'));
    new PrivacyButton(window.document.querySelector('#privacyButton'));
});

class RefreshButton {
    constructor(button: Element) {
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
