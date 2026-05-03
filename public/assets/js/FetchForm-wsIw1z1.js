/**
 * FetchForm
 * ------------------------------------------------------------------
 * Système AJAX générique
 * - submit AJAX
 * - refresh live sur input
 * - fusion des forms
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;
    this._timer = null;
    this.lastQuery = null;

    this.init();
  }

  init() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    /**
     * LIVE REFRESH
     * Déclenche une requête AJAX lors de la saisie
     */
    this.form.addEventListener("input", e => {
      const el = e.target;

      if (!(el instanceof HTMLElement)) return;
      if (!el.matches("input, select")) return;

      if (el.closest("[data-module='autocomplete']")) return;

      this.debouncedSend();
    });
  }

  /**
   * Debounce anti-spam AJAX
   */
  debouncedSend() {
    clearTimeout(this._timer);

    this._timer = setTimeout(() => {
      this.send();
    }, 250);
  }

  /**
   * Fusion des autres forms si nécessaire
   */
  merge(formData) {
    document.querySelectorAll("[data-module='fetch-form']").forEach(other => {
      if (other === this.form) return;

      new FormData(other).forEach((v, k) => {
        formData.set(k, v);
      });
    });
  }

  /**
   * Envoi AJAX principal
   */
  send() {
    const input = this.form.querySelector("input, select, textarea");

    if (input && this.lastQuery === input.value) {
      return;
    }

    if (input) {
      this.lastQuery = input.value;
    }

    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;
    const target = document.querySelector(this.form.dataset.target);

    if (!url || !target) return;

    this.isLoading = true;

    const formData = new FormData(this.form);
    this.merge(formData);

    fetch(url, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      body: formData
    })
      .then(r => r.text())
      .then(html => {
        target.innerHTML = html;
        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(err => {
        console.error("[FetchForm] error", err);
      })
      .finally(() => {
        this.isLoading = false;
      });
  }
}
