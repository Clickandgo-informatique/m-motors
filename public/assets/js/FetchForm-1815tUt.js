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
    this.form.addEventListener("input", e => this.onChange(e));
    this.form.addEventListener("change", e => this.onChange(e));

    this.form.addEventListener("submit", e => {
      e.preventDefault();
      e.stopPropagation();
      this.send();
    });

    setTimeout(() => {
      this.ready = true;
    }, 200);
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
    const targetSelector = this.form.dataset.target;

    console.log("[FetchForm] send triggered");
    console.log("[FetchForm] url:", url);
    console.log("[FetchForm] target selector:", targetSelector);

    const target = targetSelector
      ? document.querySelector(targetSelector)
      : null;

    console.log("[FetchForm] target resolved:", target);

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
    console.log("[FetchForm] query string:", params.toString());

    const fullUrl = url + "?" + params.toString();

    console.log("[FetchForm] full request URL:", fullUrl);

    fetch(fullUrl, {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
      .then(res => res.text())
      .then(html => {
        console.log("[FetchForm] response received");
        console.log("[FetchForm] injecting into target");

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
