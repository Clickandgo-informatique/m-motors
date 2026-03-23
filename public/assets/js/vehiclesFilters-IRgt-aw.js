// vehiclesFilters.js

/**
 * Module de gestion des filtres véhicules + pagination AJAX
 * Compatible avec formulaire chargé dynamiquement (fragment sidebar)
 */
export default class VehiclesFilter {
  /**
   * @param {HTMLFormElement} form
   */
  constructor(form) {
    console.log("INIT VehiclesFilter → élément reçu :", form);

    // 🔴 Sécurité absolue : vérifier que c’est bien un <form>
    if (!(form instanceof HTMLFormElement)) {
      console.error("VehiclesFilter : élément invalide (pas un form)", form);
      return;
    }

    this.form = form;

    // 🔴 Vérification dataset
    if (!this.form.dataset) {
      console.error("VehiclesFilter : dataset introuvable");
      return;
    }

    // 🔴 URL AJAX obligatoire
    this.url = this.form.dataset.fetchUrl;
    if (!this.url) {
      console.error("VehiclesFilter : data-fetch-url manquant");
      return;
    }

    console.log("URL AJAX :", this.url);

    // ✅ Initialisation des events
    this.initEvents();
  }

  /**
   * Initialise tous les événements
   */
  initEvents() {
    console.log("Init events");

    /**
     * 🎯 1. Écoute globale sur le formulaire
     * (checkboxes, inputs, sliders → tout passe ici)
     */
    this.form.addEventListener("change", e => {
      // On vérifie que c’est bien un input
      if (!e.target.matches("input")) return;

      console.log("Changement détecté :", e.target.name, e.target.value);

      this.submitFilters();
    });

    /**
     * 🎯 2. Pagination (event delegation global)
     */
    document.addEventListener("click", e => {
      const btn = e.target.closest("[data-page]");
      if (!btn) return;

      e.preventDefault();

      const page = parseInt(btn.dataset.page);

      if (isNaN(page)) return;

      console.log("Pagination demandée :", page);

      this.submitFilters(page);
    });
  }

  /**
   * Construit les filtres + envoie AJAX
   */
  async submitFilters(page = 1) {
    console.log("SubmitFilters → page :", page);

    // 📦 Récupération des données du formulaire
    const formData = new FormData(this.form);
    const filters = {};

    for (const [key, value] of formData.entries()) {
      // Gestion des tableaux (checkboxes)
      if (key.endsWith("[]")) {
        const cleanKey = key.replace(/\[\]$/, "");

        if (!filters[cleanKey]) {
          filters[cleanKey] = [];
        }

        filters[cleanKey].push(value);
      } else {
        filters[key] = value;
      }
    }

    console.log("Filters envoyés :", filters);

    /**
     * 🚀 Appel AJAX
     */
    try {
      const response = await fetch(this.url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          filters: filters,
          page: page
        })
      });

      const data = await response.json();

      console.log("Réponse AJAX :", data);

      this.updateDOM(data);
    } catch (error) {
      console.error("Erreur AJAX :", error);
    }
  }

  /**
   * Met à jour le DOM avec les fragments retournés
   */
  updateDOM(data) {
    console.log("Update DOM");

    // 🔹 Résultats
    const results = document.querySelector("#vehicles-results");
    if (results && data.results) {
      results.innerHTML = data.results;
    }

    // 🔹 Pagination TOP
    const top = document.querySelector(".pagination-wrapper.top");
    if (top && data.paginationTop) {
      top.innerHTML = data.paginationTop;
    }

    // 🔹 Pagination BOTTOM
    const bottom = document.querySelector(".pagination-wrapper.bottom");
    if (bottom && data.paginationBottom) {
      bottom.innerHTML = data.paginationBottom;
    }
  }
}

/**
 * 🔥 OBSERVER → détecte l’apparition du formulaire dans le DOM
 * (car il est chargé via fragment AJAX)
 */
function watchFiltersForm() {
  console.log("Observer démarré");

  const observer = new MutationObserver(() => {
    const form = document.querySelector("#filters-form");

    // ❌ Pas encore présent → on attend
    if (!form) return;

    // 🔴 Sécurité : vérifier que c’est bien un <form>
    if (!(form instanceof HTMLFormElement)) {
      console.error("Element trouvé mais PAS un form :", form);
      return;
    }

    // 🔴 Empêche double initialisation
    if (form.dataset.initialized) return;

    console.log("Form détecté → initialisation");

    form.dataset.initialized = "true";

    new VehiclesFilter(form);
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
}

/**
 * 🚀 Lancement au chargement de la page
 */
document.addEventListener("DOMContentLoaded", () => {
  watchFiltersForm();
});
