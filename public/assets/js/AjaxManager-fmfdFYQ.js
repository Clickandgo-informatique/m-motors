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
        const trigger = e.target.closest("[data-ajax-modal], form, a");

        if (!trigger) return;

        // si c’est un enfant d’un form/a mais pas le form lui-même
        const resolvedTrigger = this.resolveTrigger(trigger);

        if (!resolvedTrigger) return;

        e.preventDefault();
        e.stopPropagation();

        const url = this.resolveUrl(resolvedTrigger);

        if (!url) {
          console.error("[AjaxManager] URL manquante", resolvedTrigger);
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

  resolveTrigger(el) {
    if (el instanceof HTMLFormElement) return el;
    if (el instanceof HTMLAnchorElement) return el;

    const form = el.closest("form[data-ajax-modal]");
    if (form) return form;

    const link = el.closest("a[data-ajax-modal]");
    if (link) return link;

    return el;
  }

  resolveUrl(trigger) {
    if (trigger.dataset?.url && trigger.dataset.url.trim() !== "") {
      return trigger.dataset.url;
    }

    if (trigger instanceof HTMLFormElement) {
      return trigger.action;
    }

    if (trigger instanceof HTMLAnchorElement) {
      return trigger.href;
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
