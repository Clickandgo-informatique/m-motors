/**
 * FetchForm.js
 * ------------------------------------------------------------------
 * Gestion des formulaires AJAX génériques
 *
 * Fonctionnalités :
 * - Interception des submit
 * - Auto-submit optionnel (data-auto-submit)
 * - Envoi via fetch API
 * - Injection du HTML retourné dans une cible
 * - Compatible re-render AJAX (ui:updated)
 *
 * IMPORTANT :
 * - Toujours instancier avec le FORM complet
 * - Ne pas utiliser sur un input seul
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
   * Initialisation des listeners
   */
  init() {
    this.bindSubmit();
    this.bindAutoSubmit();
  }

  /**
   * Interception du submit classique
   */
  bindSubmit() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();

      this.send();
    });
  }

  /**
   * Auto-submit uniquement si activé explicitement
   * via data-auto-submit
   */
  bindAutoSubmit() {
    if (!this.form.dataset.autoSubmit) {
      return;
    }

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
   * Envoi AJAX du formulaire
   */
  send() {
    if (this.isLoading) {
      return;
    }

    const url = this.form.dataset.fetchUrl || this.form.action;

    if (!url) {
      console.warn("FetchForm: URL manquante");
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
      .then(response => {
        if (!response.ok) {
          throw new Error("FetchForm: erreur réseau");
        }
        return response.text();
      })
      .then(html => {
        this.updateTarget(html);
      })
      .catch(error => {
        console.error("FetchForm error:", error);
      })
      .finally(() => {
        this.isLoading = false;
      });
  }

  /**
   * Mise à jour du DOM cible
   */
  updateTarget(html) {
    const targetSelector = this.form.dataset.target;

    if (!targetSelector) {
      console.warn("FetchForm: data-target manquant");
      return;
    }

    const target = document.querySelector(targetSelector);

    if (!target) {
      console.warn("FetchForm: target introuvable", targetSelector);
      return;
    }

    target.innerHTML = html;

    /**
     * Réinitialisation des modules JS après update DOM
     */
    window.dispatchEvent(new Event("ui:updated"));
  }
}
