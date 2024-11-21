import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ['input', 'form'];

    connect() {
        this.inputTarget.addEventListener('change', this.#submit);
    }

    click() {
        this.inputTarget.click();
    }

    #submit = () => {
        this.formTarget.submit();
    }
}
