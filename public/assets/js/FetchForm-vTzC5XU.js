/**
 * FetchForm
 * ------------------------------------------------------------------
 * Système AJAX pour listing véhicules
 * - filtre + pagination + view grid/table
 * - réponse HTML uniquement (NE PAS utiliser JSON ici)
 * ------------------------------------------------------------------
 */

export default class FetchForm {
  constructor(form) {
    console.log("[FetchForm INIT]", form);
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;
    this._timer = null;
    this.lastQuery = null;

    this.init();
  }

  init() {
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.send();
    });

    this.form.addEventListener("input", e => {
      const el = e.target;

      if (!(el instanceof HTMLElement)) return;
      if (!el.matches("input, select, textarea")) return;

      if (el.closest("[data-module='autocomplete']")) return;

      this.debouncedSend();
    });
  }

  debouncedSend() {
    clearTimeout(this._timer);

    this._timer = setTimeout(() => {
      this.send();
    }, 250);
  }

  merge(formData) {
    document.querySelectorAll("[data-module='fetch-form']").forEach(other => {
      if (other === this.form) return;

      new FormData(other).forEach((value, key) => {
        formData.set(key, value);
      });
    });
  }

  send() {
    const input = this.form.querySelector("input, select, textarea");

    if (input && this.lastQuery === input.value) return;

    if (input) {
      this.lastQuery = input.value;
    }

    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl || this.form.action;
    const targetSelector = this.form.dataset.target;

    const target = document.querySelector(targetSelector);

    if (!url || !target) {
      console.error("[FetchForm] configuration invalide", {
        url,
        targetSelector
      });
      return;
    }

    this.isLoading = true;

    const formData = new FormData(this.form);
    this.merge(formData);

    const params = new URLSearchParams(formData);

    fetch(`${url}?${params.toString()}`, {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
      .then(response => response.text())
      .then(html => {
        target.innerHTML = html;
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
