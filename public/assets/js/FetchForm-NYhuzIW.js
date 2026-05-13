export default class FetchForm {
  constructor(form) {
    // Sécurise l'instanciation uniquement sur un formulaire HTML valide
    if (!(form instanceof HTMLFormElement)) return;

    this.form = form;
    this.isLoading = false;

    this.init();
  }

  init() {
    // Interception du submit natif du formulaire
    this.form.addEventListener("submit", async e => {
      e.preventDefault();
      e.stopPropagation();

      await this.send();
    });

    // Déclenchement automatique sur changement de champs
    this.form.addEventListener("change", async () => {
      await this.send();
    });
  }

  warnMissingDataset(key, value) {
    // Vérifie la présence d'un dataset obligatoire
    if (!value) {
      console.warn(`[FetchForm] Missing dataset: "${key}"`, this.form);
      return false;
    }

    return true;
  }

  resolveTarget(selector, name) {
    // Résout un sélecteur CSS vers un élément du DOM
    if (!selector) {
      console.warn(`[FetchForm] Missing dataset for ${name}`);
      return null;
    }

    const el = document.querySelector(selector);

    // Avertit si la cible DOM n'existe pas
    if (!el) {
      console.warn(`[FetchForm] Target not found for ${name}: ${selector}`);
    }

    return el;
  }

  async send() {
    // Empêche les appels multiples simultanés
    if (this.isLoading) return;

    const url = this.form.dataset.fetchUrl;
    const mode = this.form.dataset.fetchMode || "json";

    // Vérifie que l'URL de fetch est définie
    if (!this.warnMissingDataset("fetchUrl", url)) return;

    // Résolution des cibles DOM
    const target = this.resolveTarget(this.form.dataset.target, "target");

    const filtersTarget = this.resolveTarget(
      this.form.dataset.filtersTarget,
      "filtersTarget"
    );

    const paginationTop = this.resolveTarget(
      this.form.dataset.paginationTop,
      "paginationTop"
    );

    const paginationBottom = this.resolveTarget(
      this.form.dataset.paginationBottom,
      "paginationBottom"
    );

    // Le container principal est obligatoire
    if (!target) {
      console.error("[FetchForm] Missing target container");
      return;
    }

    this.isLoading = true;

    try {
      // Construction des paramètres GET depuis le formulaire
      const formData = new FormData(this.form);
      const params = new URLSearchParams(formData);

      // Requête en GET avec query string
      const requestUrl = `${url}?${params.toString()}`;

      const res = await fetch(requestUrl, {
        method: "GET"
      });

      // Mode HTML : injection directe du contenu
      if (mode === "html") {
        const html = await res.text();

        target.innerHTML = html;

        // Notifie les autres modules qu'une mise à jour UI a eu lieu
        window.dispatchEvent(new Event("ui:updated"));

        return;
      }

      // Mode JSON : parsing de la réponse structurée
      const data = await res.json();

      // Injection de la liste principale
      if (data.list) {
        target.innerHTML = data.list;
      }

      // Injection pagination top
      if (data.pagination_top && paginationTop) {
        paginationTop.innerHTML = data.pagination_top;
      }

      // Injection pagination bottom
      if (data.pagination_bottom && paginationBottom) {
        paginationBottom.innerHTML = data.pagination_bottom;
      }

      // Injection des filtres dynamiques si présents
      if (data.filters && filtersTarget) {
        filtersTarget.innerHTML = data.filters;
      }

      // Mise à jour du résumé des filtres si présent dans le DOM
      if (data.filtersSummary) {
        const summary = document.querySelector("#filters-summary");

        if (summary) {
          summary.innerHTML = data.filtersSummary;
        }
      }

      // Notification globale de mise à jour UI
      window.dispatchEvent(new Event("ui:updated"));
    } catch (err) {
      console.error("[FetchForm] erreur AJAX", err);
    } finally {
      this.isLoading = false;
    }
  }
}