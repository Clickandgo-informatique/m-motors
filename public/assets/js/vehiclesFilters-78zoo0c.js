// assets/js/vehiclesFilter.js

/**
 * Module VehiclesFilter
 * --------------------
 * Gère les filtres dynamiques pour la liste de véhicules avec :
 * - AJAX
 * - FetchForm.js
 * - Targets multiples (tbody + pagination)
 *
 * Usage :
 * - Mettre `data-filter-form` sur le formulaire
 * - Mettre `data-target` sur tbody et pagination
 */

export default class VehiclesFilter {
  constructor(formSelector = "[data-filter-form]") {
    this.form = document.querySelector(formSelector);

    if (!this.form) {
      console.warn("VehiclesFilter : formulaire introuvable", formSelector);
      return;
    }

    // Targets dynamiques
    this.targets = {};
    this.form
      .closest("[data-fetch-form]")
      .querySelectorAll("[data-target]")
      .forEach(el => {
        const key = el.dataset.target;
        if (!this.targets[key]) this.targets[key] = [];
        this.targets[key].push(el);
      });

    this.fetchUrl = this.form.closest("[data-fetch-form]").dataset.fetchUrl;

    this.bindEvents();
  }

  bindEvents() {
    // Changement de n'importe quel input/select
    this.form.addEventListener("change", e => {
      e.preventDefault();
      this.fetchPage(1);
    });

    // Pagination : interception des liens data-page
    this.form.closest("[data-fetch-form]").addEventListener("click", e => {
      const link = e.target.closest("[data-page]");
      if (!link) return;
      e.preventDefault();
      const page = parseInt(link.dataset.page, 10);
      if (!isNaN(page)) this.fetchPage(page);
    });

    // Submission éventuelle (si bouton submit)
    this.form.addEventListener("submit", e => {
      e.preventDefault();
      this.fetchPage(1);
    });
  }

  async fetchPage(page = 1) {
    if (!this.fetchUrl) return;

    // Construction des filtres
    const data = { filters: {}, q: null };
    const formData = new FormData(this.form);

    for (let [key, value] of formData.entries()) {
      if (key === "q") {
        data.q = value;
      } else {
        if (!data.filters[key]) data.filters[key] = [];
        data.filters[key].push(value);
      }
    }

    try {
      const response = await fetch(`${this.fetchUrl}?page=${page}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      });

      const json = await response.json();
      this.updateTargets(json);

      // Scroll top pour UX
      window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (e) {
      console.error("VehiclesFilter fetchPage error", e);
    }
  }

  updateTargets(data) {
    // Parcours de toutes les targets
    Object.entries(data).forEach(([key, html]) => {
      const targets = this.targets[key];
      if (!targets) return;
      targets.forEach(el => (el.innerHTML = html));
    });
  }
}
