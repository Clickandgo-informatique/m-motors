/**
 * FetchForm.js
 * Système AJAX générique stable
 */

export default class FetchForm {
  constructor(form) {
    this.form = form;
    this.isLoading = false;

    this.init();
  }

  /**
   * Initialisation instance
   */
  init() {
    this.bindSubmit();
    this.bindChangeDelegation();
    this.bindPagination();
  }

  /**
   * Submit classique
   */
  bindSubmit() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });
  }

  /**
   * Déclenchement sur changement de filtres
   */
  bindChangeDelegation() {
    this.form.addEventListener("change", e => {
      const el = e.target;

      if (!(el instanceof HTMLElement)) return;
      if (!el.matches("input, select, textarea")) return;

      // ignore autocomplete
      if (el.dataset.autocomplete === "true") return;

      this.send();
    });
  }

  /**
   * Pagination AJAX (IMPORTANT)
   */
  bindPagination() {
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;

      e.preventDefault();

      const page = btn.dataset.page;

      const pageInput = this.form.querySelector("input[name='page']");
      if (pageInput) {
        pageInput.value = page;
      } else {
        const hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = "page";
        hidden.value = page;
        this.form.appendChild(hidden);
      }

      this.send();
    });
  }

  /**
   * Fusion éventuelle des autres forms AJAX
   */
  mergeOtherFormsData(formData) {
    const forms = document.querySelectorAll("form[data-fetch-form]");

    forms.forEach(otherForm => {
      if (otherForm === this.form) return;
      if (!(otherForm instanceof HTMLFormElement)) return;

      const otherData = new FormData(otherForm);

      otherData.forEach((value, key) => {
        formData.set(key, value);
      });
    });
  }

  /**
   * Envoi AJAX principal
   */
  send() {
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;
    const targetSelector = this.form.dataset.target;

    const target = document.querySelector(targetSelector);

    if (!url || !target) {
      console.error("[FetchForm] missing config", { url, targetSelector });
      return;
    }

    this.isLoading = true;

    const formData = new FormData(this.form);

    this.mergeOtherFormsData(formData);

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
      .catch(err => console.error("[FetchForm] error", err))
      .finally(() => {
        this.isLoading = false;
      });
  }

  /**
   * Init globale (important après AJAX)
   */
  static initAll() {
    document.querySelectorAll("[data-fetch-form]").forEach(form => {
      if (form.dataset.fetchInit === "1") return;

      form.dataset.fetchInit = "1";
      new FetchForm(form);
    });
  }
}
