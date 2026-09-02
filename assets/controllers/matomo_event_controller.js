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
//
// Use the "trackValue" action on a form control (typically a <select>) to
// send the selected value/label as the event name instead of a static one:
//
//   {{ form_row(form.roadType, { row_attr: {
//       'data-controller': 'matomo-event',
//       'data-action': 'change->matomo-event#trackValue',
//       'data-matomo-event-category-value': 'Arrêté',
//       'data-matomo-event-action-value': 'Sélection type de localisation',
//   }}) }}
export default class extends Controller {
    static values = {
        category: String,
        action: String,
        name: String,
    };

    track() {
        this.#push(this.nameValue);
    }

    trackValue(event) {
        const target = event?.target;
        let name = this.nameValue;

        if (target) {
            if (target.tagName === 'SELECT' && target.selectedOptions?.length) {
                name = target.selectedOptions[0].text.trim();
            } else if ('value' in target) {
                name = target.value;
            }
        }

        this.#push(name);
    }

    #push(name) {
        if (!window._paq || !this.categoryValue || !this.actionValue) {
            return;
        }

        window._paq.push([
            'trackEvent',
            this.categoryValue,
            this.actionValue,
            name || undefined,
        ]);
    }
}
