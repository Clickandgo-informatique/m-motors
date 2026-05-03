export default class AjaxManager {
  constructor() {
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
    document.body.addEventListener("click", e => {
      const trigger = e.target.closest("[data-ajax-modal]");
      if (!trigger) return;

      e.preventDefault();

      const url = trigger.dataset.url || trigger.dataset.ajaxModal;
      if (!url) return;

      this.loadModal(url);
    });

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

  async loadModal(url) {
    if (this.isLoading) return;

    this.isLoading = true;

    this.modal.classList.add("open");
    this.modalBody.innerHTML = "Chargement...";

    try {
      const res = await fetch(url, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });

      this.modalBody.innerHTML = await res.text();
    } catch (e) {
      console.error("[AjaxManager] error", e);
      this.modalBody.innerHTML = "Erreur";
    } finally {
      this.isLoading = false;
    }
  }

  closeModal() {
    this.modal.classList.remove("open");
    this.modalBody.innerHTML = "";
  }
}
