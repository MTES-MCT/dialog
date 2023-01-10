import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["collectionContainer"];

    static values = {
        sequence: Number,
        prototype: String,
        entrySelector: { type: String, default: "li" },
    };

    /**
     * Store a unique key on the entry so we can find it later.
     */
    _setKey(entryEl, key) {
        entryEl.dataset["key"] = key;
    }

    _get(key) {
        return Array.from(this.collectionContainerTarget.children)
            .find(child => child.dataset["key"] == key);
    }

    addEntry(event) {
        const containerEl = document.createElement("div");

        containerEl.innerHTML = this.prototypeValue.replace(/__form_collection_key__/g, this.sequenceValue);

        let entryEl = containerEl.querySelector(this.entrySelectorValue);

        if (entryEl === null) {
            console.error(`prototype must contain an element matching '${this.entrySelectorValue}', none found`);
            console.log("prototype contents:", containerEl.innerHTML);
            return;
        }

        this._setKey(entryEl, this.sequenceValue.toString());

        this.collectionContainerTarget.appendChild(entryEl);

        this.sequenceValue++;
    }

    removeEntry(event) {
        let entryEl = this._get(event.params.key);

        if (entryEl !== undefined) {
            this.collectionContainerTarget.removeChild(entryEl);
        }
    }
}
