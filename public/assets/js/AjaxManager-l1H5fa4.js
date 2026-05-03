export default class AjaxManager {
  constructor() {
    this.modal = document.querySelector("#modal");
    this.body = document.querySelector("#modal-body");

    if (!this.modal || !this.body) return;

    this.bind();
  }

  bind() {
    document.body.addEventListener("click", e => {
      const trigger = e.target.closest("[data-ajax-modal]");
      if (!trigger) return;

      e.preventDefault();

      const url = trigger.dataset.ajaxUrl || trigger.getAttribute("href");

      this.open(url);
    });

    this.modal.addEventListener("click", e => {
      if (e.target.closest("[data-modal-close]")) {
        this.close();
      }
    });
  }

  open(url) {
    this.modal.classList.add("open");
    this.body.innerHTML = "Chargement...";

    fetch(url)
      .then(r => r.text())
      .then(html => {
        this.body.innerHTML = html;
      });
  }

  close() {
    this.modal.classList.remove("open");
    this.body.innerHTML = "";
  }
}
