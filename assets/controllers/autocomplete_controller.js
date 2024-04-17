import { Autocomplete as StimulusAutocomplete } from './_stimulus_autocomplete';

export default class Autocomplete extends StimulusAutocomplete {
  static values = {
    ...Autocomplete.values,
    extraQueryParams: { type: String, default: undefined },
  };

  connect() {
    super.connect();

    this.inputTarget.addEventListener('input', this.#onInput);
    this.inputTarget.addEventListener('focus', this.#onInputFocus);
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

    url.search = params.toString();

    return url.toString();
  }
}
