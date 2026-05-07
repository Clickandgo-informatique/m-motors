export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;
    this.timer = null;
    this.ready = false;

    this.init();
  }

  init() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();

      console.log("[FetchForm] submit intercepted");

      this.send();
    });

    setTimeout(() => {
      this.ready = true;
    }, 300);
  }

  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);

    if (!url || !target) {
      console.log("[FetchForm] missing url or target");
      return;
    }

    this.isLoading = true;

    const params = new URLSearchParams(new FormData(this.form));

    console.log("[FetchForm] request:", params.toString());

    fetch(url + "?" + params.toString(), {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
      .then(res => res.text())
      .then(html => {
        console.log("[FetchForm] response received");

        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(err => {
        console.error("[FetchForm] error", err);
      })
      .finally(() => {
        this.isLoading = false;
      });
  }
}
