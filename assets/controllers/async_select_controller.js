import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['fromSelect', 'toSelect'];
    static values = {
        url: String,
        queryParameter: String
    };

    fetch = async () => {
        const value = this.fromSelectTarget.value;
        const response = await this.doFetch(`${this.urlValue}?${this.queryParameterValue}=${value}`);
        this.toSelectTarget.innerHTML = response;
    };

    doFetch = async (url) => {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Server responded with status ${response.status}`);
        }

        const html = await response.text();

        return html;
    }
}
