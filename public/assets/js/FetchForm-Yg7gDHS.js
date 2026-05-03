/**
 * FetchForm.js
 * Système AJAX générique stable
 * ------------------------------------------------------------------
 * Fix important :
 * - autocomplete ne doit PAS déclencher FetchForm global
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  constructor(form) {
    this.form = form;
    this.isLoading = false;

    this.init();
  }

  init() {
    console.log("[FetchForm] INIT OK", this.form);

    this.bindSubmit();
    this.bindChangeDelegation();
  }

  bindSubmit() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  bindChangeDelegation() {
    this.form.addEventListener("change", e => {
      const el = e.target;

      if (!(el instanceof HTMLElement)) return;

      if (!el.matches("input, select, textarea")) return;

      /**
       * IMPORTANT :
       * ne pas déclencher FetchForm sur autocomplete
       */
      if (el.dataset.autocomplete === "true") return;

      this.send();
    });
  }

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

  send() {
    console.log("[FetchForm] SEND CALLED");

    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;
    console.log("[FetchForm] URL =", url);
    const targetSelector = this.form.dataset.target;

    const target = document.querySelector(targetSelector);

    if (!url || !target) {
      console.error("[FetchForm] missing config");
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
        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(console.error)
      .finally(() => {
        this.isLoading = false;
      });
  }
}
