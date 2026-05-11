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
      .then(res => res.json())
      .then(data => {
        console.log("[FetchForm] response received", data);

        const targetSelector = this.form.dataset.target;
        const target = document.querySelector(targetSelector);

        if (data.list) {
          target.innerHTML = data.list;
        }

        const paginationTop = document.querySelector("[data-pagination-top]");
        const paginationBottom = document.querySelector(
          "[data-pagination-bottom]"
        );

        if (paginationTop && data.pagination_top) {
          paginationTop.innerHTML = data.pagination_top;
        }

        if (paginationBottom && data.pagination_bottom) {
          paginationBottom.innerHTML = data.pagination_bottom;
        }

        window.dispatchEvent(new Event("ui:updated"));
      });
  }
}
