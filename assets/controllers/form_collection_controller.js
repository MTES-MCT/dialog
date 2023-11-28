import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['collectionContainer', 'collectionItem'];

    static values = {
        nextIndex: Number,
        prototype: String,
        prototypeKey: String,
    };

    connect() {
        // This may be readjusted when the size of the collection changes (eg. an item gets deleted).
        // But indexValue MUST NOT go down, only go up. This guarantees field element IDs remain unique.
        this._nextPosition = this.nextIndexValue + 1;
    }

    addCollectionElement(_event) {
        const el = document.createElement('div');
        el.innerHTML = this.prototypeValue
            .replace(new RegExp(`__${this.prototypeKeyValue}_name__`, 'g'), this.nextIndexValue)
            .replace(new RegExp(`__${this.prototypeKeyValue}_position__`, 'g'), this._nextPosition);

        this.collectionContainerTarget.appendChild(el.children[0]);
        this.nextIndexValue++;
        this._nextPosition++;

        this.collectionContainerTarget.removeAttribute('data-empty');
    }

    collectionItemTargetDisconnected(_element) {
        if (this.collectionItemTargets.length === 0) {
            this.collectionContainerTarget.setAttribute('data-empty', 'data-empty');
        }
    }

    syncPositions(_event) {
        this._nextPosition = this.collectionItemTargets.length + 1;

        this.collectionItemTargets.forEach((el, index) => {
            const position = index + 1;

            el.querySelectorAll('[data-form-collection-position-template]').forEach(templatedEl => {
                templatedEl.textContent = `${templatedEl.dataset.formCollectionPositionTemplate} ${position}`;
            });
        });
    }
}
