/**
 * AjaxManager
 * ------------------------------------------------------------------
 * Modales HTML uniquement
 * (NE JAMAIS appeler /ajax/search ici)
 * ------------------------------------------------------------------
 */

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

      const url = trigger.dataset.ajaxUrl || trigger.getAttribute("href");

      if (!url) return;

      this.open(url);
    });

    this.modal.addEventListener("click", e => {
      if (
        e.target.matches("[data-modal-close]") ||
        e.target.closest("[data-modal-close]")
      ) {
        this.close();
      }

      if (e.target === this.modal) {
        this.close();
      }
    });

    document.addEventListener("keydown", e => {
      if (e.key === "Escape") {
        this.close();
      }
    });
  }

  open(url) {
    if (this.isLoading) return;

    this.isLoading = true;

    this.modal.classList.add("open");
    this.modalBody.innerHTML = "Chargement...";

    fetch(url, {
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
      .then(r => r.text())
      .then(html => {
        this.modalBody.innerHTML = html;
      })
      .catch(err => {
        console.error("[AjaxManager] error", err);
        this.modalBody.innerHTML = "Erreur de chargement";
      })
      .finally(() => {
        this.isLoading = false;
      });
  }

  close() {
    this.modal.classList.remove("open");
    this.modalBody.innerHTML = "";
  }
}
