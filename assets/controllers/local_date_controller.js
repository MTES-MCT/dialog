import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static values = {
        format: String,
    };

    connect() {
        this.element.textContent = this._format(this.formatValue, this.element.dateTime);
        this.element.dataset.localized = true;
    }

    _format(format, /** @type {string} */ value) {
        const date = new Date(value);

        if (format === '%d/%m/%Y') {
            return (
                `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`
            );
        }

        throw new Error(`Unknown date format: ${format}`);
    }
}
