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
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;

    if (!url) {
      console.error("FetchForm: missing URL");
      return;
    }

    const targetSelector = this.form.dataset.target;

    if (!targetSelector) {
      console.error("FetchForm: missing data-target");
      return;
    }

    const target = document.querySelector(targetSelector);

    if (!target) {
      console.error("FetchForm: target not found", targetSelector);
      return;
    }

    this.isLoading = true;

    const formData = new FormData(this.form);

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
      .catch(err => console.error("FetchForm error:", err))
      .finally(() => {
        this.isLoading = false;
      });
  }
}
