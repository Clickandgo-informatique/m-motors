export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.abortController = null;
    this._timeout = null;

    this.init();
  }

  init() {
    this.form.addEventListener("change", e => {
      if (!this.form.contains(e.target)) return;
      this.resetPage();
      this.scheduleSend();
    });

    this.form.addEventListener("input", e => {
      if (!this.form.contains(e.target)) return;
      this.resetPage();
      this.scheduleSend();
    });

    this.form.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;

      e.preventDefault();

      const page = btn.dataset.page;

      console.log("PAGE CLICKED:", page);

      this.setPage(page);
      this.send();
    });

    const resetBtn = this.form.querySelector("[data-reset-filters]");
    if (resetBtn) {
      resetBtn.addEventListener("click", e => {
        e.preventDefault();

        this.form.reset();
        this.resetPage();
        this.send();
      });
    }
  }

  setPage(page) {
    let input = this.form.querySelector('[name="page"]');

    if (!input) {
      input = document.createElement("input");
      input.type = "hidden";
      input.name = "page";
      this.form.appendChild(input);
    }

    input.value = page;

    console.log("PAGE SET:", input.value);
  }

  resetPage() {
    this.setPage(1);
  }

  scheduleSend() {
    clearTimeout(this._timeout);

    this._timeout = setTimeout(() => {
      this.send();
    }, 120);
  }

  async send() {
    if (this.abortController) {
      this.abortController.abort();
    }

    this.abortController = new AbortController();

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);

    if (!url || !target) return;

    const formData = new FormData(this.form);
    const params = new URLSearchParams();

    formData.forEach((value, key) => {
      if (value !== "") {
        params.append(key, value);
      }
    });

    console.log("PAGE SENT:", params.get("page"));

    try {
      const res = await fetch(`${url}?${params.toString()}`, {
        signal: this.abortController.signal
      });

      const data = await res.json();

      target.innerHTML = data.results || data.list || "";

      this.renderPagination(data);

    } catch (e) {
      if (e.name !== "AbortError") {
        console.error(e);
      }
    }
  }

  renderPagination(data) {
    const top = this.form.dataset.paginationTop
      ? document.querySelector(this.form.dataset.paginationTop)
      : null;

    const bottom = this.form.dataset.paginationBottom
      ? document.querySelector(this.form.dataset.paginationBottom)
      : null;

    if (data.pagination) {
      if (top) top.innerHTML = data.pagination;
      if (bottom) bottom.innerHTML = data.pagination;
    }
  }
}