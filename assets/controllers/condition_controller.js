import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static values = {
        equals: String,
        checked: String,
    };

    static targets = ['checkbox'];

    dispatchFromInputChange(event) {
        this.dispatch(event.target.value === this.equalsValue ? 'yes' : 'no');
    }

    dispatchFromCheckboxes() {
        const checkedValues = this.checkboxTargets.filter(checkbox => checkbox.checked).map(checkbox => checkbox.value);
        this.dispatch(checkedValues.includes(this.checkedValue) ? 'yes' : 'no');
    }
}
