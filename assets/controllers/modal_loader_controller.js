import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
  static values = {
    url: String,
    targetId: String,
    modalId: String
  }

  openModal(event) {
    event.preventDefault()

    const modal = document.getElementById(this.modalIdValue)
    const content = document.getElementById(this.targetIdValue)

    fetch(this.urlValue, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(response => response.text())
      .then(html => {
        content.innerHTML = html
        modal.setAttribute('aria-hidden', 'false')
        modal.style.display = 'block'
        modal.showModal?.()
      })
      .catch(error => console.error('Erreur de chargement :', error))
  }
}
