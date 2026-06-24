import { Controller } from '@hotwired/stimulus';

// Recopie la valeur d'un ou plusieurs champs source vers des champs destination,
// appariés par clé (data-mirror-value-name). Utilisé pour propager la ville
// (cityCode + cityLabel) de la « Ville entière » vers chaque exception, dont
// l'autocomplétion de voie a besoin du code commune.
export default class extends Controller {
    static targets = ['source', 'destination'];

    connect() {
        this._boundSync = () => this.#syncAll();

        this.sourceTargets.forEach(source => {
            source.addEventListener('input', this._boundSync);
            source.addEventListener('change', this._boundSync);
        });

        this.#syncAll();
    }

    disconnect() {
        if (!this._boundSync) return;

        this.sourceTargets.forEach(source => {
            source.removeEventListener('input', this._boundSync);
            source.removeEventListener('change', this._boundSync);
        });
    }

    // Synchronise une destination ajoutée dynamiquement (ex. nouvelle ligne d'exception).
    destinationTargetConnected(destination) {
        this.#syncOne(destination);
    }

    #syncAll() {
        this.destinationTargets.forEach(destination => this.#syncOne(destination));
    }

    #syncOne(destination) {
        const source = this.#findSource(destination.dataset.mirrorValueName);

        if (source && destination.value !== source.value) {
            destination.value = source.value;
            destination.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    #findSource(name) {
        if (name) {
            const source = this.sourceTargets.find(s => s.dataset.mirrorValueName === name);
            if (source) {
                return source;
            }
        }

        return this.sourceTargets[0] ?? null;
    }
}
