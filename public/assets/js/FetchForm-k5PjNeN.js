/**
 * FetchForm.js
 * ------------------------------------------------------------------
 * RÔLE FINAL :
 * - collecte les données des forms
 * - déclenche AjaxManager
 * - NE FAIT PLUS AUCUN fetch()
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

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

      // autocomplete protection
      if (el.dataset.autocomplete === "true") return;

      this.send();
    });
  }

  mergeOtherFormsData(formData) {
    document.querySelectorAll("form[data-fetch-form]").forEach(other => {
      if (other === this.form) return;
      if (!(other instanceof HTMLFormElement)) return;

      const data = new FormData(other);

      data.forEach((value, key) => {
        formData.set(key, value);
      });
    });
  }

  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;
    const targetSelector = this.form.dataset.target;
    const target = document.querySelector(targetSelector);

    if (!url || url === "undefined") {
      console.error("[FetchForm] INVALID URL BLOCKED");
      return;
    }

    if (!target) {
      console.error("[FetchForm] TARGET MISSING");
      return;
    }

    this.isLoading = true;

    const formData = new FormData(this.form);
    this.mergeOtherFormsData(formData);

    /**
     * 🔥 DELEGATION AU AJAXMANAGER
     */
    if (!window.AjaxManagerInstance) {
      console.error("[FetchForm] AjaxManager missing");
      this.isLoading = false;
      return;
    }

    window.AjaxManagerInstance.request({
      url,
      method: "POST",
      body: formData,
      target,
      onComplete: () => {
        this.isLoading = false;
      }
    });
  }
}
