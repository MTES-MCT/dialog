import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['collectionContainer', 'collectionItem'];

    static values = {
        index: Number,
        prototype: String,
        prototypeKey: String,
    };

    addCollectionElement(_event) {
        const el = document.createElement('div');
        el.innerHTML = this.prototypeValue
            .replace(new RegExp(`__${this.prototypeKeyValue}_name__`, 'g'), this.indexValue)
            .replace(new RegExp(`__${this.prototypeKeyValue}_index__`, 'g'), this.indexValue + 1);

        this.collectionContainerTarget.appendChild(el.children[0]);
        this.indexValue++;
    }

    syncIndices(_event) {
        this.indexValue = this.collectionItemTargets.length;
        this.collectionItemTargets.forEach((el, index) => {
            el.querySelectorAll('[data-form-collection-indexed-template]').forEach(templatedEl => {
                templatedEl.textContent = `${templatedEl.dataset.formCollectionIndexedTemplate} ${index + 1}`;
            });
        });
    }
}
