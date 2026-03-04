// assets/js/AjaxManager.js

export default class AjaxManager {
  constructor(options = {}) {
    this.modalSelector = options.modalSelector || "#modal";
    this.modalBody = document.querySelector("#modal-body");

    this.modalContentSelector =
      options.modalContentSelector || "#modal-content";

    this.modal = document.querySelector(this.modalSelector);
    this.modalContent = document.querySelector(this.modalContentSelector);

    this.bindEvents();
  }

  bindEvents() {
    // Ouverture modale via lien AJAX
    document.addEventListener("click", e => {
      const link = e.target.closest("[data-ajax-modal]");
      if (link) {
        e.preventDefault();
        this.loadModal(link.dataset.ajaxModal);
      }
    });

    // Soumission AJAX d’un formulaire (new/edit)
    document.addEventListener("submit", e => {
      const form = e.target.closest("[data-ajax-form]");
      if (form) {
        e.preventDefault();
        this.submitForm(form);
      }
    });

    // Soumission AJAX d’un formulaire de suppression
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
          console.warn("Delete target not found:", target);
        }
      }
    });

    // Bouton de fermeture
    document.addEventListener("click", e => {
      if (e.target.matches("[data-modal-close]")) {
        this.closeModal();
      }
    });

    // Data-collection (ajout / suppression)
    document.addEventListener("click", e => {
      if (e.target.matches("[data-collection-add]")) {
        this.addCollectionItem(e.target);
      }
      if (e.target.matches("[data-collection-remove]")) {
        this.removeCollectionItem(e.target);
      }
    });
  }

  async loadModal(url) {
    const response = await fetch(url, {
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    });

    // Le contrôleur renvoie du JSON : { html: "..." }
    const data = await response.json();

    this.modalBody.innerHTML = data.html;
    this.modal.classList.add("open");
  }

  async submitForm(form) {
    const response = await fetch(form.action, {
      method: form.method,
      body: new FormData(form),
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    });

    // Si OK → "OK" (texte)
    // Si erreur de validation → JSON { html: "..." }
    const contentType = response.headers.get("Content-Type");

    if (response.ok && contentType.includes("text/plain")) {
      // Succès → fermer la modale
      this.closeModal();
      return;
    }

    // Sinon → réinjecter le formulaire avec erreurs
    const data = await response.json();
    this.modalBody.innerHTML = data.html;
  }

  closeModal() {
    this.modal.classList.add("closing");

    setTimeout(() => {
      this.modal.classList.remove("open", "closing");
      this.modalBody.innerHTML = "";
    }, 250);
  }

  addCollectionItem(button) {
    const container = document.querySelector(button.dataset.collectionAdd);
    const prototype = container.dataset.prototype;
    const index = container.children.length;

    const newItem = prototype.replace(/__name__/g, index);
    container.insertAdjacentHTML("beforeend", newItem);
  }

  removeCollectionItem(button) {
    button.closest("[data-collection-item]").remove();
  }
}
