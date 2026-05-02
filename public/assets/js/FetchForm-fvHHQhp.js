/**
 * FetchForm.js
 * ------------------------------------------------------------
 * Gestion des formulaires AJAX génériques (Symfony + Twig)
 * - Submit intercepté et envoyé via fetch()
 * - Support des forms dynamiques (AJAX reload)
 * - Support auto-submit sur changement (radio, select, etc.)
 * ------------------------------------------------------------
 */

export default class FetchForm {
  constructor() {
    this.init();
  }

  /**
   * Initialisation globale
   */
  init() {
    console.log("FetchForm initialisé");

    this.bindSubmit();
    this.bindAutoChange();
  }

  /**
   * Interception des submit classiques des forms AJAX
   */
  bindSubmit() {
    document.addEventListener("submit", e => {
      const form = e.target;

      if (!form.matches("[data-fetch-form]")) {
        return;
      }

      e.preventDefault();

      this.sendForm(form);
    });
  }

  /**
   * Gestion des champs qui doivent déclencher automatiquement un fetch
   * (radio, select, checkbox, etc.)
   */
  bindAutoChange() {
    document.addEventListener("change", e => {
      const el = e.target;

      const form = el.closest("[data-fetch-form]");
      if (!form) {
        return;
      }

      /**
       * On déclenche un submit programmatique
       * pour centraliser toute la logique dans sendForm()
       */
      form.dispatchEvent(
        new Event("submit", {
          bubbles: true,
          cancelable: true
        })
      );
    });
  }

  /**
   * Envoi AJAX du formulaire
   */
  sendForm(form) {
    const url = form.dataset.fetchUrl || form.action;

    if (!url) {
      console.warn("FetchForm : URL manquante");
      return;
    }

    const formData = new FormData(form);

    fetch(url, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      },
      body: formData
    })
      .then(response => {
        if (!response.ok) {
          throw new Error("Erreur réseau FetchForm");
        }
        return response.text();
      })
      .then(html => {
        this.updateTarget(form, html);
      })
      .catch(error => {
        console.error("FetchForm error:", error);
      });
  }

  /**
   * Injection du HTML retourné dans la zone cible
   */
  updateTarget(form, html) {
    const targetSelector = form.dataset.target;

    if (!targetSelector) {
      console.warn("FetchForm : data-target manquant");
      return;
    }

    const target = document.querySelector(targetSelector);

    if (!target) {
      console.warn("FetchForm : target introuvable", targetSelector);
      return;
    }

    target.innerHTML = html;
  }
}
