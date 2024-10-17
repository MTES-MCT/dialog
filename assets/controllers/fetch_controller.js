import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
        extraQueryParams: { type: String, default: undefined },
    };

    fetch = async () => {
        const response = await fetch(this.buildURL());
        if (!response.ok) {
            throw new Error(`Server responded with status ${response.status}`);
        }

        const text = await response.text();
        Turbo.renderStreamMessage(text);
    };

    buildURL = () => {
        const url = new URL(this.urlValue);
        const params = new URLSearchParams(url.search);

        if (this.extraQueryParamsValue) {
            const extraQueryParams = JSON.parse(this.extraQueryParamsValue);

            for (let [key, value] of Object.entries(extraQueryParams)) {
                if (value.startsWith('#')) {
                    value = document.querySelector(value).value;
                }

                params.append(key, value);
            }
        }

        url.search = params.toString();

        return url.toString();
    }
}
