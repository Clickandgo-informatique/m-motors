/**
 * FetchForm.js
 * Système AJAX générique stable
 * ------------------------------------------------------------------
 * Objectifs :
 * - Intercepter submit + change
 * - Envoyer une requête AJAX unique
 * - Synchroniser les autres forms data-fetch-form
 * - Éviter les doubles appels
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  constructor(form) {
    this.form = form;
    this.isLoading = false;

    this.init();
  }

  /**
   * Initialisation
   */
  init() {
    console.log("[FetchForm] INIT OK", this.form);

    this.bindSubmit();
    this.bindChangeDelegation();
  }

  /**
   * Interception submit
   */
  bindSubmit() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  /**
   * Interception des changements inputs
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
   * Fusion des données de tous les forms AJAX de la page
   */
  mergeOtherFormsData(formData) {
    const forms = document.querySelectorAll("form[data-fetch-form]");

    forms.forEach(otherForm => {
      if (otherForm === this.form) return;

      if (!(otherForm instanceof HTMLFormElement)) return;

      const otherData = new FormData(otherForm);

      otherData.forEach((value, key) => {
        formData.set(key, value);
      });
    });
  }

  /**
   * Envoi AJAX principal
   */
  send() {
    console.log("[FetchForm] SEND CALLED");

    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;
    const targetSelector = this.form.dataset.target;

    if (!url || !targetSelector) {
      console.error("[FetchForm] missing config (url or target)");
      return;
    }

    const target = document.querySelector(targetSelector);

    if (!target) {
      console.error("[FetchForm] target not found:", targetSelector);
      return;
    }

    this.isLoading = true;

    const formData = new FormData(this.form);

    // fusion des autres forms AJAX
    this.mergeOtherFormsData(formData);

    fetch(url, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      body: formData
    })
      .then(response => response.text())
      .then(html => {
        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(error => {
        console.error("[FetchForm] ERROR", error);
      })
      .finally(() => {
        this.isLoading = false;
      });
  }
}
