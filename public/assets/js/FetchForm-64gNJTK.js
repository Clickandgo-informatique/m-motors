/**
 * FetchForm.js
 * ------------------------------------------------------------------
 * Gestion générique des formulaires AJAX
 *
 * Fonctionnalités :
 * - Submit intercepté
 * - Auto-submit sur changement d'inputs
 * - Envoi via fetch API
 * - Injection HTML dans une cible définie
 * - Compatible DOM dynamique (AJAX / re-render Symfony)
 *
 * IMPORTANT :
 * - nécessite data-fetch-url ou action
 * - nécessite data-target pour injection
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  /**
   * @param {HTMLFormElement} form
   */
  constructor(form) {
    this.form = form;
    this.isLoading = false;

    this.init();
  }

  /**
   * Initialisation des comportements
   */
  init() {
    this.bindSubmit();
    this.bindInputs();
  }

  /**
   * Interception du submit natif
   */
  bindSubmit() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  /**
   * Bind direct sur tous les inputs du formulaire
   * (solution robuste pour DOM dynamique)
   */
  bindInputs() {
    const inputs = this.form.querySelectorAll("input, select, textarea");

    inputs.forEach(input => {
      input.addEventListener("change", () => {
        this.send();
      });
    });
  }

  /**
   * Envoi AJAX du formulaire
   */
  send() {
    console.log("[FetchForm] send() CALLED", this.form);

    if (this.isLoading) {
      console.warn("[FetchForm] blocked (isLoading=true)");
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
      .then(response => {
        if (!response.ok) {
          throw new Error("HTTP error: " + response.status);
        }
        return response.text();
      })
      .then(html => {
        console.log("[FetchForm] response received");

        target.innerHTML = html;

        /**
         * Permet de réinitialiser les modules JS après injection DOM
         */
        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(error => {
        console.error("[FetchForm] error:", error);
      })
      .finally(() => {
        console.log("[FetchForm] reset isLoading");
        this.isLoading = false;
      });
  }
}
