import { Controller } from "@hotwired/stimulus"
import { Idiomorph } from 'idiomorph/dist/idiomorph.esm';

const optionSelector = "[role='option']:not([aria-disabled])"
const activeSelector = "[aria-selected='true']"

export default class Autocomplete extends Controller {
  static targets = ["input", "hidden", "results", "status"]
  static classes = ["selected"]
  static values = {
    ready: Boolean,
    submitOnEnter: Boolean,
    url: String,
    minLength: Number,
    loadingStatus: { type: String, default: '' },
    emptyStatus: { type: String, default: '' },
    delay: { type: Number, default: 300 },
    queryParam: { type: String, default: "q" },
    fetchEmpty: { type: Boolean, default: false },
  }
  static uniqOptionId = 0

  connect() {
    this.close()

    // Accessibility attributes
    // See: https://www.w3.org/WAI/ARIA/apg/patterns/combobox/examples/combobox-autocomplete-list/
    // Announce that this is a combobox with list of results
    this.inputTarget.setAttribute("role", "combobox")
    this.inputTarget.setAttribute("aria-autocomplete", "list")
    // Link the input to the list of results
    const resultsId = this.resultsTarget.id
    if (!resultsId) {
      throw new Error('[a11y]: results element must have an id="..."')
    }
    this.inputTarget.setAttribute("aria-controls", resultsId)
    this.inputTarget.setAttribute('aria-describedby', resultsId) // Announce list of items and status when they change

    if(!this.inputTarget.hasAttribute("autocomplete")) this.inputTarget.setAttribute("autocomplete", "off")
    this.inputTarget.setAttribute("spellcheck", "false")

    this.mouseDown = false

    this.onInputChange = debounce(this.onInputChange, this.delayValue)

    this.inputTarget.addEventListener("keydown", this.onKeydown)
    this.inputTarget.addEventListener("blur", this.onInputBlur)
    this.inputTarget.addEventListener("input", this.onInputChange)
    this.resultsTarget.addEventListener("mousedown", this.onResultsMouseDown)
    this.resultsTarget.addEventListener("click", this.onResultsClick)

    if (this.inputTarget.hasAttribute("autofocus")) {
      this.inputTarget.focus()
    }

    if (!this.inputTarget.value && this.hasStatusTarget && this.emptyStatusValue) {
      this.statusTarget.textContent = this.emptyStatusValue
    }

    this.readyValue = true

    this._loadingDisabled = false;
  }

  disconnect() {
    if (this.hasInputTarget) {
      this.inputTarget.removeEventListener("keydown", this.onKeydown)
      this.inputTarget.removeEventListener("blur", this.onInputBlur)
      this.inputTarget.removeEventListener("input", this.onInputChange)
    }

    if (this.hasResultsTarget) {
      this.resultsTarget.removeEventListener("mousedown", this.onResultsMouseDown)
      this.resultsTarget.removeEventListener("click", this.onResultsClick)
    }
  }

  sibling(next) {
    const options = this.options
    const selected = this.selectedOption
    const index = options.indexOf(selected)
    const sibling = next ? options[index + 1] : options[index - 1]
    const def = next ? options[0] : options[options.length - 1]
    return sibling || def
  }

  selectFirst() {
    const first = this.options[0]
    if (first) {
      this.select(first)
    }
  }

  selectLast() {
    const options = this.options
    const last = options[options.length - 1]
    if (last) {
      this.select(last)
    }
  }

  select(target) {
    const previouslySelected = this.selectedOption
    if (previouslySelected) {
      previouslySelected.setAttribute("aria-selected", "false")
      previouslySelected.classList.remove(...this.selectedClassesOrDefault)
    }

    target.setAttribute("aria-selected", "true")
    target.classList.add(...this.selectedClassesOrDefault)
    this.inputTarget.setAttribute("aria-activedescendant", target.id)
    target.scrollIntoView({ behavior: "auto", block: "nearest" })
  }

  onKeydown = (event) => {
    const handler = this[`on${event.key}Keydown`]
    if (handler) handler(event)
  }

  onEscapeKeydown = (event) => {
    if (!this.resultsShown) {
      this.clear()
      return
    }

    this.close()
    event.stopPropagation()
    event.preventDefault()
  }

  onArrowDownKeydown = (event) => {
    if (!this.resultsShown) {
      this.open()
      this.selectFirst()
    } else {
      const item = this.sibling(true)
      if (item) this.select(item)
    }
    event.preventDefault()
  }

  onArrowUpKeydown = (event) => {
    if (!this.resultsShown) {
      this.open()
      this.selectLast()
    } else {
      const item = this.sibling(false)
      if (item) this.select(item)
    }
    event.preventDefault()
  }

  onTabKeydown = (event) => {
    const selected = this.selectedOption
    if (selected) this.commit(selected)
  }

  onEnterKeydown = (event) => {
    const selected = this.selectedOption
    if (selected && this.resultsShown) {
      this.commit(selected)
      if (!this.hasSubmitOnEnterValue) {
        event.preventDefault()
      }
    }
  }

  onInputBlur = () => {
    if (this.mouseDown) return
    this.close()
  }

  commit(selected) {
    if (selected.getAttribute("aria-disabled") === "true") return

    if (selected instanceof HTMLAnchorElement) {
      selected.click()
      this.close()
      return
    }

    const textValue = selected.getAttribute("data-autocomplete-label") || selected.textContent.trim()
    const value = selected.getAttribute("data-autocomplete-value") || textValue
    this.inputTarget.value = textValue

    if (this.hasHiddenTarget) {
      this.hiddenTarget.value = value
      this.hiddenTarget.dispatchEvent(new Event("input"))
      this.hiddenTarget.dispatchEvent(new Event("change"))
    } else {
      this.inputTarget.value = value
    }

    this.inputTarget.focus()
    this.close()
    this.resetOptions()

    this.element.dispatchEvent(
      new CustomEvent("autocomplete.change", {
        bubbles: true,
        detail: { value: value, textValue: textValue, selected: selected }
      })
    )
  }

  clear() {
    this.inputTarget.value = ""
    if (this.hasHiddenTarget) this.hiddenTarget.value = ""
  }

  onResultsClick = (event) => {
    if (!(event.target instanceof Element)) return
    const selected = event.target.closest(optionSelector)
    if (selected) this.commit(selected)
  }

  onResultsMouseDown = () => {
    this.mouseDown = true
    this.resultsTarget.addEventListener("mouseup", () => {
      this.mouseDown = false
    }, { once: true })
  }

  onInputChange = () => {
    if (this.hasHiddenTarget) this.hiddenTarget.value = ""

    const query = this.inputTarget.value.trim()
    if ((query && query.length >= this.minLengthValue) || (!query && this.fetchEmptyValue)) {
      this.fetchResults(query)
    } else {
      this.resetOptions()
    }
  }

  identifyOptions() {
    const prefix = this.resultsTarget.id || "stimulus-autocomplete"
    const optionsWithoutId = this.resultsTarget.querySelectorAll(`${optionSelector}:not([id])`)
    optionsWithoutId.forEach(el => el.id = `${prefix}-option-${Autocomplete.uniqOptionId++}`)
  }

  resetOptions() {
    if (this.hasStatusTarget && this.emptyStatusValue) {
      this.setStatus(this.emptyStatusValue)
    }

    this.morphResults('')
  }

  setStatus(text) {
    this.statusTarget.textContent = text
  }

  disableLoadingStatus() {
    this._loadingDisabled = true;
  }

  enableLoadingStatus() {
    this._loadingDisabled = false;
  }

  showLoadingStatus() {
    if (!this.hasStatusTarget || !this.loadingStatusValue || this._loadingDisabled) {
      return
    }
    this.resultsShown = true
    this.inputTarget.setAttribute("aria-expanded", "true")
    this.setStatus(this.loadingStatusValue)
  }

  fetchResults = async (query) => {
    if (!this.hasUrlValue) return

    const url = this.buildURL(query)
    try {
      this.element.dispatchEvent(new CustomEvent("loadstart"))
      this.showLoadingStatus()
      const html = await this.doFetch(url)
      this.replaceResults(html)
      this.element.dispatchEvent(new CustomEvent("load"))
      this.element.dispatchEvent(new CustomEvent("loadend"))
    } catch(error) {
      this.element.dispatchEvent(new CustomEvent("error"))
      this.element.dispatchEvent(new CustomEvent("loadend"))
      throw error
    }
  }

  buildURL(query) {
    const url = new URL(this.urlValue, window.location.href)
    const params = new URLSearchParams(url.search.slice(1))
    params.append(this.queryParamValue, query)
    url.search = params.toString()

    return url.toString()
  }

  doFetch = async (url) => {
    const response = await fetch(url, this.optionsForFetch())

    if (!response.ok) {
      throw new Error(`Server responded with status ${response.status}`)
    }

    const html = await response.text()
    return html
  }

  morphResults(html) {
    // Be sure to morph while keeping the <li role="status"> reference the same,
    // otherwise screen readers may not announce the new status.
    if (this.hasStatusTarget) {
      html += this.statusTarget.outerHTML;
    }

    Idiomorph.morph(this.resultsTarget, html, {
      morphStyle: 'innerHTML'
    });

    const statusTemplate = this.resultsTarget.querySelector('template[id="status"]');

    if (statusTemplate) {
      if (this.hasStatusTarget) {
        // Load HTML string into a throaway div
        const div = document.createElement('div');
        div.appendChild(statusTemplate.content.cloneNode(true));
        this.setStatus(div.textContent);
      }

      statusTemplate.remove();
    }
  }

  replaceResults(html) {
    this.close()
    this.morphResults(html)
    this.identifyOptions()
    if (!!this.options) {
      this.open()
    } else {
      this.close()
    }
  }

  open() {
    if (this.resultsShown) return

    this.resultsShown = true
    this.inputTarget.setAttribute("aria-expanded", "true")
    this.element.dispatchEvent(
      new CustomEvent("toggle", {
        detail: { action: "open", inputTarget: this.inputTarget, resultsTarget: this.resultsTarget }
      })
    )
  }

  close() {
    if (!this.resultsShown) return

    this.resultsShown = false
    this.inputTarget.removeAttribute("aria-activedescendant")
    this.inputTarget.setAttribute("aria-expanded", "false")
    this.element.dispatchEvent(
      new CustomEvent("toggle", {
        detail: { action: "close", inputTarget: this.inputTarget, resultsTarget: this.resultsTarget }
      })
    )
  }

  get resultsShown() {
    return !this.resultsTarget.hidden
  }

  set resultsShown(value) {
    this.resultsTarget.hidden = !value
  }

  get options() {
    return Array.from(this.resultsTarget.querySelectorAll(optionSelector))
  }

  get selectedOption() {
    return this.resultsTarget.querySelector(activeSelector)
  }

  get selectedClassesOrDefault() {
    return this.hasSelectedClass ? this.selectedClasses : ["active"]
  }

  optionsForFetch() {
    return { headers: { "X-Requested-With": "XMLHttpRequest" } } // override if you need
  }
}

const debounce = (fn, delay = 10) => {
  let timeoutId = null

  return (...args) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(fn, delay)
  }
}

export { Autocomplete }
