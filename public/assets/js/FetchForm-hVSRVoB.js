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
    console.log("[FetchForm] init");

    this.form.addEventListener("input", e => this.onChange(e));
    this.form.addEventListener("change", e => this.onChange(e));

    this.form.addEventListener("submit", e => {
      e.preventDefault();
      e.stopPropagation();
      this.send();
    });

    setTimeout(() => {
      this.ready = true;
    }, 400);
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

    const paginationTop = document.querySelector(
      '[data-target="pagination-top"]'
    );
    const paginationBottom = document.querySelector(
      '[data-target="pagination-bottom"]'
    );

    if (!url || !target) return;

    this.isLoading = true;

    const pageInput = this.form.querySelector('input[name="page"]');
    if (pageInput) {
      pageInput.value = 1;
    }

    const params = new URLSearchParams(new FormData(this.form));

    const fullUrl = url + "?" + params.toString();

    fetch(fullUrl, {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
      .then(res => res.json())
      .then(data => {
        if (data.list) {
          target.innerHTML = data.list;
        }

        if (data.pagination_top && paginationTop) {
          paginationTop.innerHTML = data.pagination_top;
        }

        if (data.pagination_bottom && paginationBottom) {
          paginationBottom.innerHTML = data.pagination_bottom;
        }

        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(err => {
        console.error("[FetchForm] erreur AJAX", err);
      })
      .finally(() => {
        this.isLoading = false;
      });
  }
}
