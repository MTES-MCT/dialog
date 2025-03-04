import { Controller } from '@hotwired/stimulus';
import { debounce, respondToVisibility } from '../lib';

export default class extends Controller {
    static values = {
        url: String,
        delay: { type: Number, default: 300 },
        extraQueryParams: { type: String, default: undefined },
        requiredParams: { type: String, default: undefined }, // Use to avoid unecessary requests
        prefetch: { type: Boolean, default: false },
    };

    connect() {
        // Applique un debounce court pour éviter les requêtes en double
        // dans le cas où la méthode 'fetch' est liée à des events qui arrivent quasiment en même temps.
        // Par exemple autocomplete.reset suivi d'un autocomplete.change (car la valeur est devenue "" donc a changé).
        this.fetch = debounce(this.fetch, 100);

        respondToVisibility(this.element, this.#handleVisibility);
    }

    #handleVisibility = (visible) => {
        if (visible && this.prefetchValue) {
            this.fetch();
        }
    };

    fetch = async () => {
        const url = this.buildURL();

        if (url === null) {
            return;
        }

        const response = await fetch(url);
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

        if (!this.#checkRequiredParams(params)) {
            return null;
        }

        url.search = params.toString();

        return url.toString();
    }

    /**
     * @param {URLSearchParams} params 
     * @returns {Boolean}
     */
    #checkRequiredParams(params) {
        if (!this.requiredParamsValue) {
            return true;
        }

        for (const name of JSON.parse(this.requiredParamsValue)) {
            if (!params.get(name)) {
                return false;
            }
        }

        return true;
    }
}
