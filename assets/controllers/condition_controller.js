import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static values = {
        equals: String,
    };

    dispatchFromInputChange(event) {
        this.dispatch(event.target.value === this.equalsValue ? 'yes' : 'no');
    }

    dispatchFromCheckboxChange(event) {
        this.dispatch(event.target.checked ? 'yes' : 'no');
    }
}
