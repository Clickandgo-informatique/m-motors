/**
 * FetchForm.js
 * ------------------------------------------------------------------
 * Gestion des formulaires AJAX génériques
 *
 * Fonctionnalités :
 * - Interception des submit
 * - Auto-submit sur change (radio, select, checkbox)
 * - Envoi via fetch API
 * - Injection du HTML retourné dans une cible
 * - Compatible re-render AJAX (ui:updated)
 *
 * ATTENTION :
 * Ce module doit être instancié avec le FORM complet
 * et non un input seul.
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  /**
   * @param {HTMLFormElement} form
   */
  constructor(form) {
    this.form = form;

    this.init();
  }

  /**
   * Initialisation des listeners
   */
  init() {
    this.bindSubmit();
    this.bindAutoChange();
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
   * Déclenchement automatique lors des changements
   * utile pour radios, selects, checkboxes
   */
  bindAutoChange() {
    /**
     * Auto-submit uniquement si explicitement activé
     * via data-auto-submit
     */
    if (!this.form.dataset.autoSubmit) {
      return;
    }

    this.form.addEventListener("change", e => {
      const target = e.target;

      if (
        target instanceof HTMLInputElement ||
        target instanceof HTMLSelectElement
      ) {
        this.send();
      }
    });
  }

  /**
   * Envoi AJAX du formulaire
   */
  send() {
    const url = this.form.dataset.fetchUrl || this.form.action;

    if (!url) {
      console.warn("FetchForm: URL manquante");
      return;
    }

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
     * Important :
     * permet de réinitialiser les modules JS après injection AJAX
     */
    window.dispatchEvent(new Event("ui:updated"));
  }
}
