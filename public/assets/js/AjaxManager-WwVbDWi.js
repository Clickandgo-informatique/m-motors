export default class AjaxManager {
  constructor() {
    console.log("Ajaxmanager.js initialisé");

    this.modal = document.querySelector("#modal");
    this.modalBody = document.querySelector("#modal-body");

    if (!this.modal || !this.modalBody) {
      console.warn("[AjaxManager] modal absente");
      return;
    }

    this.isLoading = false;

    this.bindEvents();
  }

  bindEvents() {
    document.body.addEventListener(
      "click",
      e => {
        const trigger = e.target.closest(
          "[data-ajax-modal], form[data-ajax-modal], a[data-ajax-modal], .vehicle-card"
        );

        if (!trigger) return;

        // bloc de sécurité UX (cards véhicules)
        if (trigger && e.target.closest(".vehicle-card-actions, .favorite-btn")) {
          return;
        }

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
    if (!trigger) return null;

    if (trigger.dataset?.url && trigger.dataset.url.trim() !== "") {
      return trigger.dataset.url;
    }

    if (trigger instanceof HTMLFormElement) {
      return trigger.action;
    }

    if (trigger instanceof HTMLAnchorElement) {
      return trigger.href;
    }

    if (trigger.dataset?.action && trigger.dataset.action.trim() !== "") {
      return trigger.dataset.action;
    }

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
        method: "GET",
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }

      const html = await res.text();
      this.modalBody.innerHTML = html;

      this.initModalComponents();
    } catch (e) {
      console.error("[AjaxManager] error", e);
      this.modalBody.innerHTML = "Erreur de chargement";
    } finally {
      this.isLoading = false;
    }
  }

  initModalComponents() {
    const inputs = this.modalBody.querySelectorAll("input[data-url]");

    inputs.forEach(input => {
      if (input.dataset.autocompleteInit === "1") {
        return;
      }

      if (typeof window.Autocomplete === "function") {
        new window.Autocomplete(input);
        input.dataset.autocompleteInit = "1";
      } else {
        console.warn("[AjaxManager] Autocomplete non disponible globalement");
      }
    });
  }

  closeModal() {
    this.modal.classList.remove("open");
    this.modalBody.innerHTML = "";
  }
}
