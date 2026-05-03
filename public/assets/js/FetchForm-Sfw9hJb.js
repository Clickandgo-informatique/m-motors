/**
 * FetchForm
 * ------------------------------------------------------------------
 * Système AJAX générique pour listing véhicules
 *
 * Fonctionnement :
 * - envoie les filtres via AJAX
 * - met à jour une zone HTML complète
 * - support du live refresh sur input
 * - compatible avec autocomplete (exclusion)
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
    /**
     * Submit manuel du formulaire
     */
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    /**
     * Live refresh sur modification des champs
     */
    this.form.addEventListener("input", e => {
      const el = e.target;

      if (!(el instanceof HTMLElement)) return;
      if (!el.matches("input, select, textarea")) return;

      /**
       * Important : ne pas interférer avec autocomplete
       */
      if (el.closest("[data-module='autocomplete']")) return;

      this.debouncedSend();
    });
  }

  /**
   * Debounce pour éviter spam AJAX
   */
  debouncedSend() {
    clearTimeout(this._timer);

    this._timer = setTimeout(() => {
      this.send();
    }, 250);
  }

  /**
   * Merge des autres filtres éventuels sur la page
   */
  merge(formData) {
    document.querySelectorAll("[data-module='fetch-form']").forEach(other => {
      if (other === this.form) return;

      new FormData(other).forEach((value, key) => {
        formData.set(key, value);
      });
    });
  }

  /**
   * Envoi AJAX principal
   */
  send() {
    const input = this.form.querySelector("input, select, textarea");

    /**
     * Anti re-fetch identique
     */
    if (input && this.lastQuery === input.value) {
      return;
    }

    if (input) {
      this.lastQuery = input.value;
    }

    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;
    const targetSelector = this.form.dataset.target;

    const target = document.querySelector(targetSelector);

    if (!url || !target) {
      console.error("[FetchForm] configuration invalide", {
        url,
        targetSelector
      });
      return;
    }

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
      .then(response => response.text())
      .then(html => {
        /**
         * IMPORTANT :
         * backend renvoie un HTML complet (table ou grid)
         */
        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(err => {
        console.error("[FetchForm] erreur AJAX", err);
      })
      .finally(() => {
        this.isLoading = false;
      });
  }
}
