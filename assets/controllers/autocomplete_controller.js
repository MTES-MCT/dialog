import { respondToVisibility } from '../lib';
import { Autocomplete as StimulusAutocomplete } from './_stimulus_autocomplete';

export default class Autocomplete extends StimulusAutocomplete {
  static values = {
    ...Autocomplete.values,
    extraQueryParams: { type: String, default: undefined },
    requiredParams: { type: String, default: undefined }, // Use to avoid unecessary requests
    prefetch: { type: Boolean, default: false },
  };

  constructor(...args) {
    super(...args);

    this._fetchManager = new FetchManager(this);
  }

  connect() {
    super.connect();

    this.inputTarget.addEventListener('input', this.#onInput);
    this.inputTarget.addEventListener('focus', this.#onInputFocus);

    this._fetchManager.connect();
  }

  disconnect() {
    super.disconnect();

    if (this.hasInputTarget) {
      this.inputTarget.removeEventListener('input', this.#onInput);
      this.inputTarget.removeEventListener('focus', this.#onInputFocus);
    }
  }

  #onInput = () => {
    if (this.hasHiddenTarget) {
      // By default this is debounced, which can lead to submitting a hidden
      // value that's out of date because we began to type something new.
      this.hiddenTarget.value = '';
    }
  }

  #onInputFocus = () => {
    // Show any existing results when entering the input
    if (this.resultsTarget.innerHTML) {
      super.open();
    }
  };

  // Overrides

  buildURL(query) {
    const url = new URL(super.buildURL(query));

    const params = new URLSearchParams(url.search);

    if (this.extraQueryParamsValue) {
      const extraQueryParams = JSON.parse(this.extraQueryParamsValue);

      for (let [key, value] of Object.entries(extraQueryParams)) {

        if (value.startsWith('#')) {
          value = document.querySelector(value).value;
        }

        params.append(key, value);
      }
    }

    if (!this.#checkRequiredParams(params)) {
      return null;
    }

    url.search = params.toString();

    return url.toString();
  }

  /**
   * @param {URLSearchParams} params 
   * @returns {Boolean}
   */
  #checkRequiredParams(params) {
    if (!this.requiredParamsValue) {
      return true;
    }

    for (const name of JSON.parse(this.requiredParamsValue)) {
      if (!params.get(name)) {
        return false;
      }
    }

    return true;
  }

  // Action callbacks

  reset() {
    this.resetOptions();
    this._fetchManager.reset();
  }
}

class FetchManager {
  /**
   * @param {Autocomplete} controller 
   */
  constructor(controller) {
    this._controller = controller;
    this._isFetchRequested = controller.prefetchValue;
    this._isFetching = false;
  }

  connect() {
    respondToVisibility(this._controller.element, this.#handleVisibility);

    this._controller.element.addEventListener('loadend', this.#onLoadEnd);
  }

  reset() {
    this._isFetchRequested = this._controller.prefetchValue;

    if (this._isFetchRequested) {
      this.#doManagedFetch();
    }
  }

  #handleVisibility = (visible) => {
    if (visible && this._isFetchRequested) {
      // Flush any previous fetch request
      this.#doManagedFetch();
      this._isFetchRequested = false;
    }
  };

  #doManagedFetch = () => {
    this._isFetching = true;
 
    this._controller.element.dispatchEvent(new CustomEvent('autocomplete.prefetch', { bubbles: true }));
    this._controller.triggerFetch();
  };

  #onLoadEnd = () => {
    const isFocused = this._controller.inputTarget === document.activeElement;
    const hasResults = !!this._controller.resultsTarget.innerHTML;

    if (isFocused && hasResults) {
      this._controller.open();
      return;
    }

    if (this._isFetching) {
      // This function is called just before the autocomplete request handling finishes ('loadend' event).
      // If we come here, it means the results come from a managed request.
      // Don't show them yet, they will be shown when user focuses the input element.
      this._controller.close();
    }
  };
}
