import { Controller } from '@hotwired/stimulus';

// Replaces the synchronous browser download with a fetch + blob download
// so the link can show a "Génération en cours…" state while the server
// renders the document (the regulation map export takes a few seconds).
export default class extends Controller {
    static values = {
        loadingLabel: String,
        errorLabel: String,
    };

    #busy = false;

    async download(event) {
        event.preventDefault();

        if (this.#busy) return;
        this.#busy = true;

        const originalText = this.element.textContent;
        this.element.setAttribute('aria-busy', 'true');
        this.element.setAttribute('aria-disabled', 'true');
        this.element.style.pointerEvents = 'none';
        this.element.style.opacity = '0.6';
        this.element.textContent = this.loadingLabelValue;

        try {
            const response = await fetch(this.element.href);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            const blob = await response.blob();
            const filename = this.#extractFilename(response, 'export.docx');
            this.#triggerBrowserDownload(blob, filename);
        } catch (err) {
            console.error('Export download failed:', err);
            alert(this.errorLabelValue || 'Erreur lors du téléchargement.');
        } finally {
            this.element.textContent = originalText;
            this.element.removeAttribute('aria-busy');
            this.element.removeAttribute('aria-disabled');
            this.element.style.pointerEvents = '';
            this.element.style.opacity = '';
            this.#busy = false;
        }
    }

    #triggerBrowserDownload(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }

    #extractFilename(response, fallback) {
        const cd = response.headers.get('Content-Disposition') || '';
        const match = /filename\*?=(?:UTF-8'')?["']?([^"';]+)/i.exec(cd);
        return match ? decodeURIComponent(match[1]) : fallback;
    }
}
