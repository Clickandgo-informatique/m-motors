// assets/js/AjaxManager.js

export default class AjaxManager {
  constructor(options = {}) {
    // Sélecteurs de la modale principale
    this.modalSelector = options.modalSelector || "#modal";
    this.modalContentSelector =
      options.modalContentSelector || "#modal-content";

    // Références DOM
    this.modal = document.querySelector(this.modalSelector);
    this.modalBody = document.querySelector("#modal-body");
    this.modalContent = document.querySelector(this.modalContentSelector);

    // Initialisation des événements globaux
    this.bindEvents();
  }

  /**
   * Bind des événements globaux du système AJAX
   */
  bindEvents() {
    /**
     * Ouverture de modale via data-ajax-modal
     * IMPORTANT : ne doit jamais interférer avec autocomplete
     */
    document.addEventListener("click", e => {
      const trigger = e.target.closest("[data-ajax-modal]");
      if (!trigger) return;

      // Empêche la propagation vers d'autres systèmes UI (autocomplete, forms)
      if (trigger.closest(".dropdown-results")) return;

      e.preventDefault();

      const url = this.resolveUrl(trigger);

      if (!url) {
        console.warn("AjaxManager: URL invalide pour la modale", trigger);
        return;
      }

      this.loadModal(url);
    });

    /**
     * Soumission AJAX de formulaire standard
     */
    document.addEventListener("submit", e => {
      const form = e.target.closest("[data-ajax-form]");
      if (!form) return;

      e.preventDefault();
      this.submitForm(form);
    });

    /**
     * Suppression AJAX
     */
    document.addEventListener("submit", async e => {
      const form = e.target.closest("[data-ajax-delete]");
      if (!form) return;

      e.preventDefault();

      if (!confirm("Supprimer cet élément ?")) return;

      const response = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (response.ok) {
        const target = form.dataset.deleteTarget;
        const el = document.querySelector(target);

        if (el) {
          el.remove();
        } else {
          console.warn("AjaxManager: cible de suppression introuvable", target);
        }
      }
    });

    /**
     * Fermeture de la modale
     */
    document.addEventListener("click", e => {
      if (e.target.matches("[data-modal-close]")) {
        this.closeModal();
      }
    });

    /**
     * Gestion des collections Symfony
     */
    document.addEventListener("click", e => {
      if (e.target.matches("[data-collection-add]")) {
        this.addCollectionItem(e.target);
      }

      if (e.target.matches("[data-collection-remove]")) {
        this.removeCollectionItem(e.target);
      }
    });
  }

  /**
   * Résolution de l'URL de la modale
   * Priorité :
   * - data-ajax-modal (URL directe)
   * - data-item-link (fallback projet)
   * - href
   */
  resolveUrl(element) {
    const url =
      element.dataset.ajaxModal ||
      element.dataset.itemLink ||
      element.getAttribute("href");

    if (!url || url === "undefined") {
      return null;
    }

    return url;
  }

  /**
   * Chargement du contenu de la modale via AJAX
   */
  async loadModal(url) {
    const response = await fetch(url, {
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    });

    const contentType = response.headers.get("content-type");

    if (contentType && contentType.includes("application/json")) {
      const data = await response.json();
      this.modalBody.innerHTML = data.html;
    } else {
      const html = await response.text();
      this.modalBody.innerHTML = html;
    }

    this.modal.classList.add("open");
  }

  /**
   * Soumission AJAX de formulaire
   */
  async submitForm(form) {
    const response = await fetch(form.action, {
      method: form.method,
      body: new FormData(form),
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    });

    const contentType = response.headers.get("content-type");

    // Cas succès simple (texte brut)
    if (response.ok && contentType && contentType.includes("text/plain")) {
      this.closeModal();
      return;
    }

    // Cas formulaire avec erreurs (HTML ou JSON)
    if (contentType && contentType.includes("application/json")) {
      const data = await response.json();
      this.modalBody.innerHTML = data.html;
    } else {
      const html = await response.text();
      this.modalBody.innerHTML = html;
    }
  }

  /**
   * Fermeture de la modale avec animation
   */
  closeModal() {
    this.modal.classList.add("closing");

    setTimeout(() => {
      this.modal.classList.remove("open", "closing");
      this.modalBody.innerHTML = "";
    }, 250);
  }

  /**
   * Ajout d’un item dans une collection Symfony
   */
  addCollectionItem(button) {
    const container = document.querySelector(button.dataset.collectionAdd);
    if (!container) return;

    const prototype = container.dataset.prototype;
    const index = container.children.length;

    const newItem = prototype.replace(/__name__/g, index);

    container.insertAdjacentHTML("beforeend", newItem);
  }

  /**
   * Suppression d’un item de collection Symfony
   */
  removeCollectionItem(button) {
    const item = button.closest("[data-collection-item]");
    if (item) {
      item.remove();
    }
  }
}
