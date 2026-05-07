export default class FetchForm {
  constructor(form) {
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;
    this.timer = null;
    this.ready = true;

    this.init();
  }

  init() {
    this.form.addEventListener("input", e => this.onChange(e));
    this.form.addEventListener("change", e => this.onChange(e));

    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  onChange(e) {
    const el = e.target;
    if (!(el instanceof HTMLElement)) return;

    if (el.closest("[data-module='autocomplete']")) return;

    this.debounce();
  }

  debounce() {
    clearTimeout(this.timer);

    this.timer = setTimeout(() => {
      this.send();
    }, 200);
  }

  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;
    const target = document.querySelector(this.form.dataset.target);

    if (!url || !target) return;

    this.isLoading = true;

    const formData = new FormData(this.form);

    // DEBUG IMPORTANT
    console.log("FORM DATA:");
    console.log([...formData.entries()]);

    const params = new URLSearchParams();

    // anti doublon q
    const seen = new Set();

    for (const [k, v] of formData.entries()) {
      if (seen.has(k)) continue;
      seen.add(k);
      params.append(k, v);
    }

    const fullUrl = url + "?" + params.toString();

    console.log("FETCH URL:", fullUrl);

    fetch(fullUrl, {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
      .then(res => res.text())
      .then(html => {
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
