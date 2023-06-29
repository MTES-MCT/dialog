import { Controller } from '@hotwired/stimulus';

/**
 * Toggle the date picker when user clicks anywhere on the input, handling browser compatibility.
 */
export default class extends Controller {
    connect() {
        this.element.addEventListener('click', this.#onClick);
    }

    disconnect() {
        this.element.addEventListener('click', this.#onClick);
    }

    #onClick = (event) => {
        if (!('showPicker' in HTMLInputElement.prototype)) {
            // E.g. Safari. Can't really do much in this case.
            // See: https://developer.mozilla.org/en-US/docs/Web/API/HTMLInputElement/showPicker#browser_compatibility
            return;
        }

        /** @type {HTMLInputElement} */
        const el = event.currentTarget;

        // By default, browsers show a native picker button, which toggles the picker.
        // In most cases, DSFR will hide it, and we can safely toggle the picker ourselves.
        const isNativeButtonHiddenByDSFR = CSS.supports('selector(::-webkit-calendar-picker-indicator)');

        if (isNativeButtonHiddenByDSFR) {
            el.showPicker();
            return;
        }

        // Sometimes DSFR won't be able to hide the native button (due to https://github.com/GouvernementFR/dsfr/issues/411).
        // Then, we should only call showPicker() if the user hasn't clicked on the native button.
        // Otherwise showPicker() would effectively close the picker back and the picker wouldn't be toggled at all.

        // Determine whether the user has clicked on the native picker.
        // NOTE: This relies on a visual estimation of the relative position of the native picker on Firefox,
        // in terms of multiples of the amount of right padding on .fr-input elements.
        const rect = el.getBoundingClientRect();
        const baseSize = parseInt(getComputedStyle(el).paddingRight);
        const nativePickerX = rect.right - 2.5 * baseSize; // Visual estimation
        const nativePickerWidth = 1.5 * baseSize; // Visual estimation
        const hasClickedOnNativePicker = nativePickerX < event.clientX && event.clientX < nativePickerX + nativePickerWidth;

        if (hasClickedOnNativePicker) {
            // Let the browser open the picker.
            return;
        }

        // Otherwise, show the picker ourselves.
        el.showPicker();
    };
}
