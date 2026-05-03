/**
 * FetchForm.js
 * ------------------------------------------------------------------
 * Système AJAX générique
 *
 * Responsabilité :
 * - envoyer les formulaires en AJAX
 * - injecter le HTML retourné
 * - dispatcher ui:updated
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    // Ignore explicit forms (autocomplete par exemple)
    if (form.dataset.ignoreFetch === "1") return;

    this.form = form;
    this.isLoading = false;

    this.init();
  }

  init() {
    this.bindSubmit();
    this.bindChangeDelegation();
  }

  /**
   * Submit classique
   */
  bindSubmit() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  /**
   * Détection changement champs
   */
  bindChangeDelegation() {
    this.form.addEventListener("change", e => {
      const el = e.target;

      if (!(el instanceof HTMLElement)) return;
      if (!el.matches("input, select, textarea")) return;

      // Ignore autocomplete
      if (el.closest("[data-autocomplete='true']")) return;

      this.send();
    });
  }

  /**
   * Fusion des autres formulaires (filtres globaux)
   */
  mergeOtherFormsData(formData) {
    const forms = document.querySelectorAll(
      "form[data-fetch-form]:not([data-ignore-fetch='1'])"
    );

    forms.forEach(otherForm => {
      if (otherForm === this.form) return;

      const otherData = new FormData(otherForm);

      otherData.forEach((value, key) => {
        formData.set(key, value);
      });
    });
  }

  /**
   * Envoi AJAX
   */
  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;

    const targetSelector = this.form.dataset.target;
    const target =
      this.form.closest("main")?.querySelector(targetSelector) ||
      document.querySelector(targetSelector);

    if (!url || !targetSelector || !target) {
      console.error("[FetchForm] configuration invalide");
      return;
    }

    this.isLoading = true;

    const formData = new FormData(this.form);

    this.mergeOtherFormsData(formData);

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

        // notify re-init
        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(err => console.error("[FetchForm]", err))
      .finally(() => {
        this.isLoading = false;
      });
  }
}
