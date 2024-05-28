// @ts-check


// credits to https://github.com/fairnesscoop/permacoop/blob/master/src/assets/customElements/autoForm.js
export default class extends HTMLElement {
    connectedCallback() {
 	requestAnimationFrame(() => {
	    // Progressive enhancement:
	    // If this custom element activates, submit the form whenever
	    // a form control changes value.
	    const form = /** @type {HTMLFormElement} */ (this.querySelector('form'));
	    const associatedTurboFrameId = this.dataset['turboFrameId'];
	    const updateURLWith = this.dataset['updateUrlWith'];
	    if (associatedTurboFrameId && updateURLWith) {
		const associatedTurboFrame = document.getElementById(associatedTurboFrameId);
		for (const formControl of form.elements) {
		    if (associatedTurboFrame) {
			formControl.addEventListener('change', () => { // a change on one element of the form submits the form automatically
			    const hashString = window.location.hash; // the hash string from the URL contains '#mapZoomAndPosition=…' ; it contains the '#' character ; it is lost when the event 'turbo:frame-load' is fired, so we need to transmit it from here
			    // update the url without reloading the page - complementary with 'data-turbo-action="replace"' inside the <form>
			    // credits : https://www.30secondsofcode.org/js/s/modify-url-without-reload/
			    associatedTurboFrame.addEventListener('turbo:frame-load', () => {
				const queryString = window.location.search; // contains the '?' character
				const currentTitle = document.title;
				const nextState = {};
				window.history.replaceState(nextState, currentTitle, `${updateURLWith}${queryString}${hashString}`);
			    });
			    form.requestSubmit();
			});
		    }
		}
	    }
	});
    }
}
