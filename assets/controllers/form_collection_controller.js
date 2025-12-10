import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['collectionContainer', 'collectionItem'];

    static values = {
        nextIndex: Number,
        prototype: String,
        prototypeKey: String,
    };

    connect() {
        this._nextPosition = this.nextIndexValue + 1;
    }

    addCollectionElement(_event) {
        const newItem = this._createItemFromPrototype();
        this.collectionContainerTarget.appendChild(newItem);
        this._incrementIndices();
        this.collectionContainerTarget.removeAttribute('data-empty');
    }

    duplicateCollectionElement(event) {
        const sourceItem = event.target.closest('[data-form-collection-target="collectionItem"]');
        if (!sourceItem) return;

        const newItem = this._createItemFromPrototype();
        this._copyAllValues(sourceItem, newItem);

        sourceItem.after(newItem);
        this._incrementIndices();
        this.collectionContainerTarget.removeAttribute('data-empty');

        setTimeout(() => this._syncUIWithState(newItem), 50);
    }

    collectionItemTargetDisconnected(_element) {
        if (this.collectionItemTargets.length === 0) {
            this.collectionContainerTarget.setAttribute('data-empty', 'data-empty');
        }
    }

    syncPositions(_event) {
        this._nextPosition = this.collectionItemTargets.length + 1;

        this.collectionItemTargets.forEach((el, index) => {
            el.querySelectorAll('[data-form-collection-position-template]').forEach(templatedEl => {
                templatedEl.textContent = `${templatedEl.dataset.formCollectionPositionTemplate} ${index + 1}`;
            });
        });
    }

    _createItemFromPrototype() {
        const el = document.createElement('div');
        el.innerHTML = this.prototypeValue
            .replace(new RegExp(`__${this.prototypeKeyValue}_name__`, 'g'), this.nextIndexValue)
            .replace(new RegExp(`__${this.prototypeKeyValue}_position__`, 'g'), this._nextPosition);
        return el.children[0];
    }

    _incrementIndices() {
        this.nextIndexValue++;
        this._nextPosition++;
    }

    _copyAllValues(sourceItem, newItem) {
        this._copyDirectInputs(sourceItem, newItem);
        this._copyNestedCollections(sourceItem, newItem);
    }

    _copyDirectInputs(sourceItem, newItem) {
        this._getDirectInputs(sourceItem).forEach(sourceInput => {
            const newInput = this._findMatchingInput(newItem, sourceInput);
            if (newInput) {
                this._copyInputValue(sourceInput, newInput);
            }
        });
    }

    _getDirectInputs(item) {
        return Array.from(item.querySelectorAll('input, select, textarea')).filter(input =>
            input.name && this._isDirectChild(input, item)
        );
    }

    _isDirectChild(element, parent) {
        const closestItem = element.closest('[data-form-collection-target="collectionItem"]');
        return !closestItem || closestItem === parent;
    }

    _findMatchingInput(newItem, sourceInput) {
        if ((sourceInput.type === 'checkbox' || sourceInput.type === 'radio') && sourceInput.value) {
            const byValue = this._findInputByNamePattern(newItem, sourceInput.name, sourceInput.value);
            if (byValue) return byValue;
        }

        const fieldName = this._extractFieldName(sourceInput.name);
        return fieldName ? this._findInputByFieldName(newItem, fieldName) : null;
    }

    _findInputByNamePattern(newItem, namePattern, value) {
        const normalizedPattern = this._normalizeFormName(namePattern);

        return this._getDirectInputs(newItem).find(input =>
            this._normalizeFormName(input.name) === normalizedPattern && input.value === value
        );
    }

    _findInputByFieldName(newItem, fieldName) {
        return this._getDirectInputs(newItem).find(input =>
            this._extractFieldName(input.name) === fieldName
        );
    }

    _normalizeFormName(name) {
        return name.replace(/\[periods\]\[\d+\]/, '[periods][INDEX]');
    }

    _extractFieldName(name) {
        const match = name.match(/\[[^\]]+\]\[(\d+)\]\[([^\]]+)\](.*)$/);
        return match ? match[2] + match[3] : null;
    }

    _copyInputValue(sourceInput, newInput) {
        if (sourceInput.type === 'checkbox' || sourceInput.type === 'radio') {
            newInput.checked = sourceInput.checked;
            newInput.toggleAttribute('checked', sourceInput.checked);

            const button = newInput.closest('label')?.querySelector('button[aria-pressed]');
            if (button) {
                button.setAttribute('aria-pressed', sourceInput.checked ? 'true' : 'false');
            }
        } else {
            newInput.value = sourceInput.value;
        }
    }

    _copyNestedCollections(sourceItem, newItem) {
        const sourceCollections = sourceItem.querySelectorAll('[data-form-collection-target="collectionContainer"]');

        sourceCollections.forEach((sourceCollection, index) => {
            const sourceItems = this._getDirectCollectionItems(sourceCollection);
            if (sourceItems.length === 0) return;

            const newCollection = this._findMatchingCollection(newItem, sourceCollection, index);
            if (!newCollection) return;

            this._replaceCollectionItems(newCollection, sourceItems);
        });
    }

    _replaceCollectionItems(collection, sourceItems) {
        collection.innerHTML = '';

        sourceItems.forEach(sourceItem => {
            const clonedItem = sourceItem.cloneNode(true);
            this._updateNestedIndices(clonedItem);
            this._copyFormValuesToClone(sourceItem, clonedItem);
            collection.appendChild(clonedItem);
        });

        collection.removeAttribute('data-empty');
    }

    _copyFormValuesToClone(sourceItem, clonedItem) {
        const sourceInputs = sourceItem.querySelectorAll('input, select, textarea');
        const clonedInputs = clonedItem.querySelectorAll('input, select, textarea');

        sourceInputs.forEach((sourceInput, index) => {
            const clonedInput = clonedInputs[index];
            if (clonedInput) {
                if (sourceInput.type === 'checkbox' || sourceInput.type === 'radio') {
                    clonedInput.checked = sourceInput.checked;
                } else {
                    clonedInput.value = sourceInput.value;
                }
            }
        });
    }

    _getDirectCollectionItems(collection) {
        return Array.from(collection.children).filter(child =>
            child.hasAttribute('data-form-collection-target') &&
            child.getAttribute('data-form-collection-target').includes('collectionItem')
        );
    }

    _findMatchingCollection(newItem, sourceCollection, fallbackIndex) {
        const collectionId = sourceCollection.id || sourceCollection.getAttribute('data-testid');

        if (collectionId) {
            const byId = newItem.querySelector(`[id="${collectionId}"], [data-testid="${collectionId}"]`);
            if (byId) return byId;
        }

        const newCollections = newItem.querySelectorAll('[data-form-collection-target="collectionContainer"]');
        return newCollections[fallbackIndex];
    }

    _updateNestedIndices(element) {
        const parentIndex = this.nextIndexValue;
        const periodPattern = /\[periods\]\[\d+\]/g;
        const periodIdPattern = /periods_\d+_/g;
        const periodReplacement = `[periods][${parentIndex}]`;
        const periodIdReplacement = `periods_${parentIndex}_`;

        element.querySelectorAll('input, select, textarea, label').forEach(el => {
            if (el.name) el.name = el.name.replace(periodPattern, periodReplacement);
            if (el.id) el.id = el.id.replace(periodIdPattern, periodIdReplacement);
            if (el.htmlFor) el.htmlFor = el.htmlFor.replace(periodIdPattern, periodIdReplacement);
        });

        element.querySelectorAll('[aria-labelledby]').forEach(el => {
            const labelledBy = el.getAttribute('aria-labelledby');
            if (labelledBy) {
                el.setAttribute('aria-labelledby', labelledBy.replace(periodIdPattern, periodIdReplacement));
            }
        });
    }

    _syncUIWithState(newItem) {
        this._syncChipButtons(newItem);
        this._triggerConditionalDisplays(newItem);
    }

    _syncChipButtons(newItem) {
        newItem.querySelectorAll('[data-chip-button-target="checkbox"]').forEach(checkbox => {
            if (!this._isDirectChild(checkbox, newItem)) return;

            const button = checkbox.closest('label')?.querySelector('button[aria-pressed]');
            if (button) {
                button.setAttribute('aria-pressed', checkbox.checked ? 'true' : 'false');
            }
        });
    }

    _triggerConditionalDisplays(newItem) {
        const conditionalElements = 'select[data-action*="condition"], select[data-action*="form-reveal"], ' +
                                   'input[type="checkbox"][data-action*="condition"], input[type="checkbox"][data-action*="form-reveal"]';

        newItem.querySelectorAll(conditionalElements).forEach(element => {
            if (this._isDirectChild(element, newItem)) {
                element.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }
}
