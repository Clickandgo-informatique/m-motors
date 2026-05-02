export default class AjaxManager {
  constructor() {
    this.modal = document.querySelector("#modal");
    this.modalBody = document.querySelector("#modal-body");

    if (!this.modal || !this.modalBody) {
      console.warn("AjaxManager: modal absente");
      return;
    }

    if (this.initialized) return;
    this.initialized = true;

    this.bindEvents();
  }

  bindEvents() {
    document.body.addEventListener("click", e => {
      const trigger = e.target.closest("[data-ajax-modal]");
      if (!trigger) return;

      e.preventDefault();
      this.loadModal(trigger.dataset.ajaxModal || trigger.href);
    });

    document.body.addEventListener("submit", e => {
      const form = e.target.closest("[data-ajax-form]");
      const del = e.target.closest("[data-ajax-delete]");

      if (!form && !del) return;

      e.preventDefault();

      if (del) return this.handleDelete(del);
      if (form) return this.submitForm(form);
    });
  }

  async loadModal(url) {
    const res = await fetch(url);
    this.modalBody.innerHTML = await res.text();
    this.modal.classList.add("open");
  }

  closeModal() {
    this.modal.classList.remove("open");
    this.modalBody.innerHTML = "";
  }
}
