export default class FetchForm {
  constructor(form) {
    this.form = form;
    this.isLoading = false;

    this.init();
  }

  init() {
    this.bindSubmit();
    this.bindChangeDelegation();
  }

  bindSubmit() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  bindChangeDelegation() {
    this.form.addEventListener("change", e => {
      const el = e.target;

      if (!(el instanceof HTMLElement)) return;
      if (!el.matches("input, select, textarea")) return;

      if (el.dataset.autocomplete === "true") return;

      this.send();
    });
  }

  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;
    const targetSelector = this.form.dataset.target;

    const target = document.querySelector(targetSelector);

    if (!url || !target) {
      console.error("[FetchForm] missing config");
      return;
    }

    this.isLoading = true;

    const formData = new FormData(this.form);

    const filters = {};

    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+?)\](\[\])?$/);
      if (!match) continue;

      const name = match[1];
      const isArray = !!match[2];

      if (!filters[name]) {
        filters[name] = isArray ? [] : null;
      }

      if (isArray) {
        filters[name].push(value);
      } else {
        filters[name] = value;
      }
    }

    const payload = {
      filters,
      q: formData.get("q") || null,
      page: this.form.querySelector("input[name='page']")?.value || 1
    };

    fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      },
      body: JSON.stringify(payload)
    })
      .then(r => r.text())
      .then(html => {
        target.innerHTML = html;
        window.dispatchEvent(new Event("ui:updated"));
      })
      .catch(console.error)
      .finally(() => {
        this.isLoading = false;
      });
  }
}
