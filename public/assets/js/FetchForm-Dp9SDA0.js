export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;
    this.timer = null;

    this.init();
  }

  init() {
    this.form.addEventListener("submit", e => {
      console.log("[FetchForm] submit intercepted");

      e.preventDefault();
      e.stopPropagation();

      this.send();
    });
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

    console.log("[FetchForm] target found:", target);

    if (!url || !target) {
      console.error("[FetchForm] missing url or target");
      return;
    }

    this.isLoading = true;

    const formData = new FormData(this.form);
    const params = new URLSearchParams(formData);

    console.log("[FetchForm] params:", [...formData.entries()]);
    console.log("[FetchForm] query string:", params.toString());

    const fullUrl = url + "?" + params.toString();

    console.log("[FetchForm] request:", fullUrl);

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
