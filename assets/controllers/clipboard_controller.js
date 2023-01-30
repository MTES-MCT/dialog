import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static values = { url: String };

    copy() {
        const content = this.urlValue;

        navigator.clipboard.writeText(content).then(() => {
            console.log(`Successfully copied : ${content}`)
        });
    }
}
