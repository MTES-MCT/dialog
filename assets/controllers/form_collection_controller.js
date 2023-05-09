import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['collectionContainer'];

    static values = {
        index: Number,
        prototype: String,
    };

    addCollectionElement(_event) {
        const el = document.createElement('div');
        el.innerHTML = this.prototypeValue.replace(/__name__/g, this.indexValue).replace(/__oneBasedIndex__/g, this.indexValue + 1);
        this.collectionContainerTarget.appendChild(el.children[0]);
        this.indexValue++;
    }
}
