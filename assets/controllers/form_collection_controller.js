import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['collectionContainer'];

    static values = {
        index: Number,
        prototype:String,
    };

    addCollectionElement(_event) {
        const item = document.createElement('li');
        item.className = 'app-list-item';
        item.innerHTML = this.prototypeValue.replace(/__name__/g, this.indexValue);
        this.collectionContainerTarget.appendChild(item);
        this.indexValue++;
    }
}
