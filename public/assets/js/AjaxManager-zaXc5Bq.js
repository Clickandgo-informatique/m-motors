/**
 * AjaxManager
 * ------------------------------------------------------------------
 * Gestion centralisée :
 * - modales AJAX
 * - submit AJAX
 * - delete AJAX
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

    this.isOpen = false;
    this.isLoading = false;

    this.bindEvents();
  }

  bindEvents() {
    document.body.addEventListener("click", e => {
      const trigger = e.target.closest("[data-ajax-modal]");
      if (!trigger) return;

      if (e.target.matches("input, textarea, select")) return;
      if (e.target.closest("[data-module='autocomplete']")) return;

      e.preventDefault();

      const url =
        trigger.dataset.ajaxUrl ||
        trigger.getAttribute("data-ajax-url") ||
        trigger.getAttribute("href");

      if (!url || url === "true") {
        console.error("[AjaxManager] URL invalide", trigger);
        return;
      }

      this.open(url);
    });

    document.body.addEventListener("submit", e => {
      const form = e.target.closest("[data-ajax-form]");
      const del = e.target.closest("[data-ajax-delete]");

      if (!form && !del) return;

      e.preventDefault();

      if (del) return this.handleDelete(del);
      if (form) return this.submitForm(form);
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

  async open(url) {
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

      const html = await res.text();

      this.modalBody.innerHTML = html;
      this.isOpen = true;

      window.dispatchEvent(new Event("ui:updated"));
    } catch (e) {
      console.error("[AjaxManager] open error", e);
      this.modalBody.innerHTML = "Erreur de chargement";
    } finally {
      this.isLoading = false;
    }
  }

  close() {
    this.modal.classList.remove("open");
    this.modalBody.innerHTML = "";
    this.isOpen = false;
  }

  handleDelete(el) {
    console.log("[AjaxManager] delete", el);
  }

  submitForm(form) {
    console.log("[AjaxManager] submit", form);
  }
}
