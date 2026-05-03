/**
 * FetchForm.js
 * Système AJAX générique stable
 * ------------------------------------------------------------------
 * FIX IMPORTANTS :
 * - data-target peut être absent ou mal lu
 * - ignore certains forms (autocomplete)
 * - debug propre
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    // Ignore explicit forms (ex: autocomplete si taggué)
    if (form.dataset.ignoreFetch === "1") return;

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

      // IMPORTANT : ignore autocomplete inputs
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
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;

    const targetSelector =
      this.form.dataset.target || this.form.getAttribute("data-target");

    console.log("[FetchForm] SEND", { url, targetSelector });

    const target = document.querySelector(targetSelector);

    if (!url || !targetSelector || !target) {
      console.error("[FetchForm] missing config", {
        url,
        targetSelector,
        targetExists: !!target
      });
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
      .catch(err => console.error("[FetchForm] error", err))
      .finally(() => {
        this.isLoading = false;
      });
  }
}
