import { Controller } from '@hotwired/stimulus';

// Generic, reusable Matomo event tracking controller.
//
// Add it declaratively to any element (link, button…) to send a Matomo
// event without writing dedicated JavaScript:
//
//   <a href="…"
//      data-controller="matomo-event"
//      data-action="click->matomo-event#track"
//      data-matomo-event-category-value="Arrêté"
//      data-matomo-event-action-value="Consultation"
//      data-matomo-event-name-value="{{ identifier }}">
//
// The "name" value is optional. See:
// https://matomo.org/faq/reports/event-tracking-with-matomo/
export default class extends Controller {
    static values = {
        category: String,
        action: String,
        name: String,
    };

    track() {
        if (!window._paq || !this.categoryValue || !this.actionValue) {
            return;
        }

        window._paq.push([
            'trackEvent',
            this.categoryValue,
            this.actionValue,
            this.nameValue || undefined,
        ]);
    }
}
