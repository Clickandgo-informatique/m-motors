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

    if (!url || !target) return;

    this.isLoading = true;

    const pageInput = this.form.querySelector('input[name="page"]');
    if (pageInput) {
      pageInput.value = 1;
    }

    const formData = new FormData(this.form);

    console.log([...formData.entries()]);

    const params = new URLSearchParams(formData);

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
