/**
 * FetchForm.js
 * ------------------------------------------------------------------
 * Gestion AJAX générique des forms :
 * - filtres
 * - toggle view
 * - autocomplete
 *
 * IMPORTANT :
 * - fonctionne uniquement si data-target est défini
 * - ne mélange pas les comportements (auto-submit optionnel)
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  constructor(form) {
    this.form = form;
    this.isLoading = false;

    this.init();
  }

  init() {
    this.bindSubmit();
    this.bindAutoSubmit();
  }

  /**
   * Submit manuel (toujours actif)
   */
  bindSubmit() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  /**
   * Auto submit uniquement si activé explicitement
   */
  bindAutoSubmit() {
    if (!this.form.dataset.autoSubmit) return;

    this.form.addEventListener("change", e => {
      const el = e.target;

      if (
        el instanceof HTMLInputElement ||
        el instanceof HTMLSelectElement ||
        el instanceof HTMLTextAreaElement
      ) {
        this.send();
      }
    });
  }

  /**
   * AJAX request
   */
  send() {
    console.log("[FetchForm] send() CALLED", this.form);

    if (this.isLoading) {
      console.warn("[FetchForm] blocked by isLoading");
      return;
    }

    const url = this.form.dataset.fetchUrl || this.form.action;
    console.log("[FetchForm] URL:", url);

    if (!url) {
      console.error("[FetchForm] missing URL");
      return;
    }

    const targetSelector = this.form.dataset.target;
    console.log("[FetchForm] TARGET:", targetSelector);

    if (!targetSelector) {
      console.error("[FetchForm] missing data-target");
      return;
    }

    const target = document.querySelector(targetSelector);
    console.log("[FetchForm] TARGET ELEMENT:", target);

    if (!target) {
      console.error("[FetchForm] target NOT FOUND");
      return;
    }

    this.isLoading = true;

    const formData = new FormData(this.form);

    console.log("[FetchForm] SENDING REQUEST...");

    fetch(url, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      body: formData
    })
      .then(r => r.text())
      .then(html => {
        console.log("[FetchForm] RESPONSE OK");

        target.innerHTML = html;
        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(err => {
        console.error("[FetchForm] ERROR:", err);
      })
      .finally(() => {
        console.log("[FetchForm] FINALLY RESET isLoading");
        this.isLoading = false;
      });
  }
}
