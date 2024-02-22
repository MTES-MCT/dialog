import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static outlets = ['output'];
    static values = {
        trigger: String,
        url: String,
        queryParams: {
            type: Object,
            default: undefined,
        },
    };

    connect() {
        this.element.addEventListener(this.triggerValue, this.#onTrigger);
    }

    disconnect() {
        this.element.removeEventListener(this.triggerValue, this.#onTrigger);
    }

    #onTrigger = async () => {
        const params = new URLSearchParams();

        if (this.queryParamsValue) {
            for (let [name, value] of Object.entries(this.queryParamsValue)) {
                if (value.startsWith('#')) {
                    value = document.querySelector(value).value;
                }

                params.append(name, value);
            }
        }

        const url = `${this.urlValue}?${params.toString()}`;

        try {
            const response = await fetch(url);

            if (!response.ok) {
                console.error(`${response.status} ${response.statusText}`);
                return;
            }

            const tmpDiv = document.createElement('div');
            tmpDiv.innerHTML = await response.text();
            const output = /** @type {HTMLOutputElement} */ (tmpDiv.querySelector('output'));

            this.outputOutletElement.value = output.value;
        } catch (error) {
            console.error(`Error: ${error}`);
            return;
        }
    };
}
