// assets/js/FetchForm.js
// ==========================
// FetchForm v1 – Recherche / autocomplete / pagination AJAX
// ==========================

export default class FetchForm {
    constructor(form) {
        if (!(form instanceof HTMLElement)) throw new Error("FetchForm: élément non valide");
        this.form = form;

        // Lecture des datasets
        this.url = form.dataset.searchForm || form.action;
        this.resultDivId = form.dataset.resultDiv || "results";
        this.itemClass = form.dataset.itemClass || "vehicle-item";
        this.linkClass = form.dataset.linkClass || "vehicle-link";
        this.noResultsClass = form.dataset.noResultsClass || "dropdown-no-results";
        this.itemUrlTemplate = form.dataset.itemUrl || "";
        this.resultLinks = form.dataset.resultLinks === "true";
        this.ajaxModal = form.dataset.ajaxModal === "true";
        this.autocomplete = form.dataset.autocomplete === "true";

        // Container pour les résultats
        this.resultDiv = document.getElementById(this.resultDivId);
        if (!this.resultDiv) {
            console.warn(`[FetchForm] Container #${this.resultDivId} introuvable`);
            return;
        }

        // Input de recherche
        this.input = form.querySelector('input[name="' + (form.name || "q") + '"]');
        if (!this.input) console.warn("[FetchForm] Aucun input trouvé pour la recherche");

        // Bind events
        this.bindEvents();
    }

    bindEvents() {
        if (!this.input) return;

        // Déclenchement au clavier avec debounce
        let debounceTimer;
        this.input.addEventListener("input", () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => this.search(), 250);
        });

        // Bouton recherche manuel
        const btn = this.form.querySelector("[data-search-toggle]");
        if (btn) {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                this.search();
            });
        }
    }

    async search() {
        if (!this.url) return;

        const query = this.input.value.trim();
        const params = new URLSearchParams({ q: query });

        try {
            const res = await fetch(this.url + "?" + params.toString(), {
                method: "GET",
                headers: { "X-Requested-With": "XMLHttpRequest" }
            });
            const data = await res.json();

            if (!data.results) {
                this.renderNoResults();
            } else {
                this.renderResults(data.results);
            }
        } catch (err) {
            console.error("[FetchForm] Erreur fetch:", err);
        }
    }

    renderNoResults() {
        this.resultDiv.innerHTML = `<div class="${this.noResultsClass}">Aucun résultat trouvé</div>`;
    }

    renderResults(html) {
        // On injecte d'abord le HTML renvoyé par Symfony
        this.resultDiv.innerHTML = html;

        // Si le flag resultLinks est actif, transformer les items en <a>
        if (this.resultLinks) {
            const items = this.resultDiv.querySelectorAll(`.${this.itemClass}`);
            if (!items) return;

            items.forEach(item => {
                const url = item.dataset.url || this.itemUrlTemplate.replace("ID_PLACEHOLDER", item.dataset.id || "");
                if (!url) return;

                // Ne pas dupliquer le lien si déjà <a>
                if (!item.querySelector(`.${this.linkClass}`)) {
                    const link = document.createElement("a");
                    link.href = url;
                    link.className = this.linkClass;
                    link.innerHTML = item.innerHTML;

                    // Si modal AJAX, prevent default et déclencher modal
                    if (this.ajaxModal) {
                        link.addEventListener("click", (e) => {
                            e.preventDefault();
                            this.openModal(url);
                        });
                    }

                    item.innerHTML = "";
                    item.appendChild(link);
                }
            });
        }
    }

    openModal(url) {
        // Placeholder pour ouvrir modal AJAX
        // Ici tu peux utiliser ton code modal préféré
        console.log("[FetchForm] ouverture modal:", url);
        // Exemple : fetch(url) et injecter dans #modal
    }
}

// Initialisation automatique sur tous les forms fetch-form
document.addEventListener("DOMContentLoaded", () => {
    const forms = document.querySelectorAll(".fetch-form, [data-fetch-form]");
    forms.forEach(form => new FetchForm(form));
});
