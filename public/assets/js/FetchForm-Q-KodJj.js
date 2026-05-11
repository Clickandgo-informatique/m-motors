export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;
    this.timer = null;

    this.init();
  }

  init() {
    this.form.addEventListener("submit", async e => {
      console.log("[FetchForm] submit intercepted");

      e.preventDefault();
      e.stopPropagation();

      await this.send();
    });
  }

  async send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;
    const targetSelector = this.form.dataset.target;
    const mode = this.form.dataset.fetchMode || "html";

    console.log("[FetchForm] send triggered");
    console.log("[FetchForm] url:", url);
    console.log("[FetchForm] target:", targetSelector);
    console.log("[FetchForm] mode:", mode);

    const target = targetSelector
      ? document.querySelector(targetSelector)
      : null;

    if (!url || !target) {
      console.error("[FetchForm] missing url or target");
      return;
    }

    this.isLoading = true;

    try {
      const formData = new FormData(this.form);
      const params = new URLSearchParams(formData);

      const fullUrl = url + "?" + params.toString();

      console.log("[FetchForm] request:", fullUrl);

      const res = await fetch(fullUrl, {
        method: "GET",
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      // =========================
      // MODE HTML (dashboard)
      // =========================
      if (mode === "html") {
        const html = await res.text();
        target.innerHTML = html;

        window.dispatchEvent(new Event("ui:updated"));
        return;
      }

      // =========================
      // MODE JSON (listing public)
      // =========================
      const data = await res.json();

      console.log("[FetchForm] response JSON:", data);

      if (data.list) {
        target.innerHTML = data.list;
      }

      if (data.pagination_top) {
        const top = document.querySelector("[data-pagination-top]");
        if (top) top.innerHTML = data.pagination_top;
      }

      if (data.pagination_bottom) {
        const bottom = document.querySelector("[data-pagination-bottom]");
        if (bottom) bottom.innerHTML = data.pagination_bottom;
      }

      window.dispatchEvent(new Event("ui:updated"));
    } catch (err) {
      console.error("[FetchForm] error", err);
    } finally {
      this.isLoading = false;
    }
  }
}
