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
    this.form.__fetchFormInstance = this;

    this.form.addEventListener("input", e => this.onChange(e));
    this.form.addEventListener("change", e => this.onChange(e));

    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    setTimeout(() => {
      this.ready = true;
    }, 300);
  }

  onChange(e) {
    if (!this.ready) return;

    const el = e.target;
    if (!(el instanceof HTMLElement)) return;

    if (el.closest("[data-module='autocomplete']")) return;

    this.debounce();
  }

  debounce() {
    clearTimeout(this.timer);

    this.timer = setTimeout(() => {
      this.send();
    }, 250);
  }

  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);

    console.log("[FetchForm] send triggered");
    console.log("[FetchForm] url:", url);
    console.log("[FetchForm] target:", target);

    if (!url || !target) {
      console.error("[FetchForm] missing url or target");
      return;
    }

    this.isLoading = true;

    const pageInput = this.form.querySelector('input[name="page"]');
    if (pageInput) {
      pageInput.value = 1;
    }

    const formData = new FormData(this.form);
    const params = new URLSearchParams(formData);

    console.log("[FetchForm] params:", [...formData.entries()]);
    console.log("[FetchForm] query:", params.toString());

    const fullUrl = url + "?" + params.toString();

    fetch(fullUrl, {
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
