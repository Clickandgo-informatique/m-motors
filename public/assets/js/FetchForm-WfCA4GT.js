/**
 * FetchForm.js
 * ------------------------------------------------------------------
 * Gestion AJAX des formulaires
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
    this.bindSubmit();
    this.bindDelegatedInputs();
  }

  /**
   * Submit intercepté
   */
  bindSubmit() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  /**
   * IMPORTANT :
   * Event delegation → fonctionne même après remplacement DOM AJAX
   */
  bindDelegatedInputs() {
    this.form.addEventListener("change", e => {
      const target = e.target;

      if (!target) return;

      if (target.matches("input, select, textarea")) {
        console.log("[FetchForm] delegated change detected:", target.name);

        this.send();
      }
    });
  }

  /**
   * Envoi AJAX
   */
  send() {
    console.log("[FetchForm] send() CALLED");

    if (this.isLoading) {
      console.warn("[FetchForm] blocked (isLoading)");
      return;
    }

    const url = this.form.dataset.fetchUrl || this.form.action;

    if (!url) {
      console.error("[FetchForm] missing URL");
      return;
    }

    const targetSelector = this.form.dataset.target;

    if (!targetSelector) {
      console.error("[FetchForm] missing data-target");
      return;
    }

    const target = document.querySelector(targetSelector);

    if (!target) {
      console.error("[FetchForm] target not found:", targetSelector);
      return;
    }

    this.isLoading = true;

    const formData = new FormData(this.form);

    console.log("[FetchForm] sending request...");

    fetch(url, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      body: formData
    })
      .then(r => r.text())
      .then(html => {
        console.log("[FetchForm] response OK");

        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(err => {
        console.error("[FetchForm] error:", err);
      })
      .finally(() => {
        this.isLoading = false;
      });
  }
}
