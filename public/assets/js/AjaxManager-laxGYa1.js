// assets/js/AjaxManager.js

export default class AjaxManager {
  constructor(options = {}) {
    this.modalSelector = options.modalSelector || "#modal";
    this.modalContentSelector =
      options.modalContentSelector || "#modal-content";

    this.modal = document.querySelector(this.modalSelector);
    this.modalBody = document.querySelector("#modal-body");
    this.modalContent = document.querySelector(this.modalContentSelector);

    this.bindEvents();
  }

  bindEvents() {
    // Ouverture modale via lien AJAX
    document.addEventListener("click", e => {
      const link = e.target.closest("a[data-ajax-modal]");
      if (!link) return;

      e.preventDefault();

      // Fermer la dropdown autocomplete si ouverte
      const dropdown = document.querySelector(".dropdown-results");
      if (dropdown) {
        dropdown.classList.remove("active");
        dropdown.innerHTML = "";
      }

      this.loadModal(link.href);
    });

    // Soumission AJAX formulaire (new/edit)
    document.addEventListener("submit", e => {
      const form = e.target.closest("[data-ajax-form]");
      if (!form) return;

      e.preventDefault();

      this.submitForm(form);
    });

    // Soumission AJAX suppression
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

    // Fermeture modale
    document.addEventListener("click", e => {
      if (e.target.matches("[data-modal-close]")) {
        this.closeModal();
      }
    });

    // Gestion collection Symfony
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

    const contentType = response.headers.get("content-type");

    if (contentType && contentType.includes("application/json")) {
      const data = await response.json();
      this.modalBody.innerHTML = data.html;
    } else {
      // fallback si jamais le contrôleur renvoie directement du HTML
      const html = await response.text();
      this.modalBody.innerHTML = html;
    }

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

    const contentType = response.headers.get("content-type");

    // Succès → controller renvoie "OK"
    if (response.ok && contentType && contentType.includes("text/plain")) {
      this.closeModal();
      return;
    }

    // Sinon → formulaire avec erreurs
    if (contentType && contentType.includes("application/json")) {
      const data = await response.json();
      this.modalBody.innerHTML = data.html;
    }
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
