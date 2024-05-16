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

	    // update the url without reloading the page - complementary with 'data-turbo-action="replace"' inside the <form>
	    // credits : https://www.30secondsofcode.org/js/s/modify-url-without-reload/
	    const associatedTurboFrameId = this.dataset['turbo-frame-id'];
	    const updateURLWith = this.dataset['update-url-with'];
	    if (associatedTurboFrameId && updateURLWith) {
		const associatedTurboFrame = document.getElementById(associatedTurboFrameId);
		if (associatedTurboFrame) {
		    associatedTurboFrame.addEventListener('turbo:frame-load', () => {
			const queryString = window.location.search;
			const currentTitle = document.title;
			const nextState = {};
			window.history.replaceState(nextState, currentTitle, `${updateURLWith}${queryString}`);
		    });
		}
	    }
	});
    }
}
