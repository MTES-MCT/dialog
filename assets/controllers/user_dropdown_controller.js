import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["menu", "button", "chevron"];

  connect() {
    this.outsideClickListener = this.closeIfClickedOutside.bind(this);
  }

  toggle(event) {
    event.stopPropagation();
    const expanded = this.buttonTarget.getAttribute("aria-expanded") === "true";
    this.buttonTarget.setAttribute("aria-expanded", !expanded);
    this.menuTarget.hidden = expanded;
    this.chevronTarget.style.transform = !expanded ? "rotate(-135deg)" : "rotate(45deg)";
    if (!expanded) {
      document.addEventListener("click", this.outsideClickListener);
    } else {
      document.removeEventListener("click", this.outsideClickListener);
    }
  }

  closeIfClickedOutside(event) {
    if (!this.element.contains(event.target)) {
      this.buttonTarget.setAttribute("aria-expanded", false);
      this.menuTarget.hidden = true;
      this.chevronTarget.style.transform = "rotate(45deg)";
      document.removeEventListener("click", this.outsideClickListener);
    }
  }
}
