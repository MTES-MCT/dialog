import { Controller } from '@hotwired/stimulus';

const ONE_WAY_MEASURE_TYPE = 'oneWay';
const DIRECTION_BOTH = 'BOTH';
const DEFAULT_ONE_WAY_DIRECTION = 'A_TO_B';

// Type de restriction « Sens unique » : « Double sens » n'a pas de signification. L'option est
// retirée des selects « Sens concerné » de la mesure tant que ce type est choisi (la règle est
// aussi validée côté serveur, voir SaveMeasureCommandConstraintValidator).
export default class extends Controller {
    static targets = ['type', 'direction'];

    typeTargetConnected() {
        this.refresh();
    }

    // Appelé aussi pour les localisations ajoutées après coup au formulaire
    directionTargetConnected(select) {
        this.#apply(select);
    }

    refresh() {
        this.directionTargets.forEach((select) => this.#apply(select));
    }

    #apply(select) {
        // Les exceptions d'une localisation décrivent une emprise exclue, pas un sens de circulation
        if (select.closest('.app-whole-city-exception')) {
            return;
        }

        const both = Array.from(select.options).find((option) => option.value === DIRECTION_BOTH);

        if (!both) {
            return;
        }

        const isOneWay = this.hasTypeTarget && this.typeTarget.value === ONE_WAY_MEASURE_TYPE;

        both.hidden = isOneWay;
        both.disabled = isOneWay;

        if (isOneWay && select.value === DIRECTION_BOTH) {
            // Sens imposé par le contrôleur, pas choisi par l'utilisateur : on s'en souvient
            select.dataset.oneWayDirectionForced = 'true';
            this.#setDirection(select, DEFAULT_ONE_WAY_DIRECTION);
        } else if (!isOneWay && select.dataset.oneWayDirectionForced) {
            // Le type n'est plus « Sens unique » : un sens que l'utilisateur n'a pas choisi ne doit
            // pas restreindre à son insu la nouvelle mesure à un seul sens
            if (select.value === DEFAULT_ONE_WAY_DIRECTION) {
                this.#setDirection(select, DIRECTION_BOTH);
            }

            delete select.dataset.oneWayDirectionForced;
        }
    }

    #setDirection(select, value) {
        select.value = value;
        // Les aperçus carte écoutent ce changement pour afficher ou retirer la flèche de sens
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
