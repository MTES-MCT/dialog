import { Autocomplete as StimulusAutocomplete } from './_stimulus_autocomplete';

export default class Autocomplete extends StimulusAutocomplete {
  static values = {
    ...Autocomplete.values,
    extraQueryParams: { type: String, default: undefined },
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
