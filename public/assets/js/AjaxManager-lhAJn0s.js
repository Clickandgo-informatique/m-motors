export default class AjaxManager {
  constructor() {
    console.log("Ajaxmanager.js initialisé");

    this.modal = document.querySelector("#modal");
    this.modalBody = document.querySelector("#modal-body");

    if (!this.modal || !this.modalBody) {
      console.warn("AjaxManager: modal absente");
      return;
    }

    this.isLoading = false;

    this.bindEvents();
  }

  bindEvents() {
    document.body.addEventListener(
      "click",
      e => {
        const trigger = e.target.closest("[data-ajax-modal]");
        if (!trigger) return;

        e.preventDefault();
        e.stopPropagation();

        const url = this.resolveUrl(trigger);

        if (!url) {
          console.error("[AjaxManager] URL manquante", trigger);
          return;
        }

        this.loadModal(url);
      },
      true
    );

    this.modal.addEventListener("click", e => {
      if (e.target.closest("[data-modal-close]")) {
        this.closeModal();
      }
    });

    document.addEventListener("keydown", e => {
      if (e.key === "Escape") {
        this.closeModal();
      }
    });
  }

  resolveUrl(trigger) {
    // 1. dataset explicit URL (si tu veux override)
    if (trigger.dataset.url && trigger.dataset.url.trim() !== "") {
      return trigger.dataset.url;
    }

    // 2. form submit (cas M-Motors / modals CRUD)
    if (trigger.tagName === "FORM") {
      return trigger.action;
    }

    // 3. links classiques
    if (trigger.tagName === "A") {
      return trigger.getAttribute("href");
    }

    // 4. fallback sécurité
    console.warn("[AjaxManager] aucun resolver pour", trigger);
    return null;
  }

  async loadModal(url) {
    if (this.isLoading) return;

    this.isLoading = true;

    this.modal.classList.add("open");
    this.modalBody.innerHTML = "Chargement...";

    try {
      const res = await fetch(url, {
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }

      this.modalBody.innerHTML = await res.text();
    } catch (e) {
      console.error("[AjaxManager] error", e);
      this.modalBody.innerHTML = "Erreur de chargement";
    } finally {
      this.isLoading = false;
    }
  }

  closeModal() {
    this.modal.classList.remove("open");
    this.modalBody.innerHTML = "";
  }
}
