// @ts-check


// credits to https://github.com/fairnesscoop/permacoop/blob/master/src/assets/customElements/autoForm.js
export default class extends HTMLElement {
    connectedCallback() {
 	requestAnimationFrame(() => {
	    // Progressive enhancement:
	    // If this custom element activates, submit the form whenever
	    // a form control changes value.

	    const form = /** @type {HTMLFormElement} */ (this.querySelector('form'));

	    for (const formControl of form.elements) {
		formControl.addEventListener('change', () => form.requestSubmit());
	    }
	});
    }
}
