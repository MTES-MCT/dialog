import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    const closeBtn = this.element.querySelector('button[aria-label="Fermer"]');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => this.close());
    }
  }

  open() {
    this.element.setAttribute('aria-hidden', 'false');
    this.element.style.display = 'block';
    this.element.showModal?.();
  }

  close() {
    this.element.setAttribute('aria-hidden', 'true');
    this.element.style.display = 'none';
    this.element.querySelector('[id$="-content"]').innerHTML = '';
  }
}
