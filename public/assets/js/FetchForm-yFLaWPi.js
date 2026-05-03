/**
 * FetchForm.js
 * ------------------------------------------------------------------
 * Système AJAX générique stable
 *
 * Objectifs :
 * - Gestion submit + change
 * - Support multi-formulaires (synchronisation automatique)
 * - Réinitialisation UI via event "ui:updated"
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  constructor(form) {
    this.form = form;
    this.isLoading = false;

    this.init();
  }

  /**
   * Initialisation globale
   */
  init() {
    this.bindSubmit();
    this.bindChangeDelegation();
  }

  /**
   * Interception du submit
   */
  bindSubmit() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  /**
   * Détection des changements sur inputs
   */
  bindChangeDelegation() {
    this.form.addEventListener("change", e => {
      const el = e.target;

      if (el.matches("input, select, textarea")) {
        this.send();
      }
    });
  }

  /**
   * Synchronise les autres formulaires data-fetch-form
   * → permet d'envoyer tous les filtres ensemble
   */
  syncOtherForms(formData) {
    const forms = document.querySelectorAll("form[data-fetch-form]");

    forms.forEach(otherForm => {
      if (otherForm === this.form) return;

      const inputs = otherForm.querySelectorAll("input, select, textarea");

      inputs.forEach(input => {
        if (!input.name) return;

        // évite d’écraser une valeur déjà présente
        if (formData.has(input.name)) return;

        formData.append(input.name, input.value);
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

    const target = document.querySelector(targetSelector);
    if (!url || !target) return;

    this.isLoading = true;

    const formData = new FormData(this.form);

    // IMPORTANT : récupérer tous les autres champs AJAX de la page
    document.querySelectorAll("form[data-fetch-form]").forEach(otherForm => {
      if (otherForm === this.form) return;

      new FormData(otherForm).forEach((value, key) => {
        formData.set(key, value);
      });
    });

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
      .catch(console.error)
      .finally(() => {
        this.isLoading = false;
      });
  }
}
