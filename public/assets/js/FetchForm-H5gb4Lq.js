/**
 * FetchForm.js
 *
 * Gestion des formulaires AJAX et autocomplete
 * Compatible avec :
 * - data-fetch-form
 * - data-autocomplete
 * - data-item-url + data-ajax-modal
 * - data-item-class / data-link-class
 * - data-no-results-class
 * - pagination
 *
 * Vérifie également les datasets obligatoires et logge en console
 */

export default class FetchForm {
    constructor(form) {
        if (!(form instanceof HTMLFormElement)) return;
        this.form = form;

        // --- Initialisation des datasets ---
        this.resultDivId = form.dataset.resultDiv || 'results';
        this.autocomplete = form.dataset.autocomplete === 'true';
        this.pagination = form.dataset.pagination === 'true';
        this.itemUrl = form.dataset.itemUrl || null;
        this.ajaxModal = form.dataset.ajaxModal === 'true';
        this.itemClass = form.dataset.itemClass || 'item';
        this.linkClass = form.dataset.linkClass || 'link';
        this.noResultsClass = form.dataset.noResultsClass || 'no-results';
        this.dropdownClass = form.dataset.dropdownClass || 'dropdown-results';

        // --- Récupération du container de résultats ---
        this.resultsContainer = document.getElementById(this.resultDivId);
        if (!this.resultsContainer) {
            console.error(`[FetchForm] Container #${this.resultDivId} introuvable`);
            return;
        }

        // --- Vérification des datasets essentiels ---
        if (this.autocomplete && !this.itemUrl) {
            console.warn('[FetchForm] data-item-url non défini, les résultats ne seront pas cliquables');
        }

        // --- Bind des événements ---
        this.bindEvents();
    }

    bindEvents() {
        // Détection du submit (recherche standard)
        this.form.addEventListener('submit', e => {
            e.preventDefault();
            this.search();
        });

        // Détection du champ input pour autocomplete
        if (this.autocomplete) {
            const input = this.form.querySelector('input[name="' + (this.form.name || 'q') + '"]');
            if (!input) return;

            let debounceTimeout = null;
            input.addEventListener('input', () => {
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(() => this.search(input.value), 300);
            });

            // Effacement du champ => vider les résultats
            input.addEventListener('blur', () => {
                if (input.value.trim() === '') this.clearResults();
            });
        }
    }

    search(query = null) {
        const formData = new FormData(this.form);

        if (query !== null) {
            formData.set('q', query);
        }

        const url = this.form.dataset.fetchUrl || this.form.action;
        if (!url) {
            console.error('[FetchForm] URL de fetch introuvable');
            return;
        }

        fetch(url, {
            method: this.form.method || 'GET',
            body: this.form.method.toUpperCase() === 'POST' ? JSON.stringify(Object.fromEntries(formData)) : null,
            headers: { 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.results) {
                console.error('[FetchForm] Le serveur n\'a pas renvoyé "results"');
                return;
            }
            this.renderResults(data.results);
        })
        .catch(err => console.error('[FetchForm] Erreur fetch', err));
    }

    renderResults(resultsHtml) {
        if (!this.resultsContainer) return;

        // --- Nettoyage précédent ---
        this.resultsContainer.innerHTML = '';

        // --- Vérification du HTML ---
        if (!resultsHtml || resultsHtml.trim() === '') {
            const div = document.createElement('div');
            div.className = this.noResultsClass;
            div.textContent = 'Aucun résultat';
            this.resultsContainer.appendChild(div);
            return;
        }

        // --- Création d'un fragment temporaire ---
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = resultsHtml;

        // --- Transformation en items cliquables si dataset défini ---
        const items = tempDiv.querySelectorAll(`.${this.itemClass}`);
        if (!items.length) {
            console.warn('[FetchForm] Aucun élément trouvé avec la classe', this.itemClass);
        }

        items.forEach(item => {
            const url = item.dataset.url || this.itemUrl?.replace('ID_PLACEHOLDER', item.dataset.id || '');
            if (!url) return;

            if (this.ajaxModal) {
                item.addEventListener('click', e => {
                    e.preventDefault();
                    this.openModal(url);
                });
            } else {
                const link = document.createElement('a');
                link.href = url;
                link.className = this.linkClass;
                while (item.firstChild) link.appendChild(item.firstChild);
                item.appendChild(link);
            }

            this.resultsContainer.appendChild(item);
        });
    }

    clearResults() {
        if (this.resultsContainer) this.resultsContainer.innerHTML = '';
    }

    openModal(url) {
        // Fonctionalité modale simplifiée
        const modal = document.createElement('div');
        modal.className = 'fetchform-modal';
        modal.style.position = 'fixed';
        modal.style.top = 0;
        modal.style.left = 0;
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.background = 'rgba(0,0,0,0.5)';
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.zIndex = 9999;

        const content = document.createElement('iframe');
        content.src = url;
        content.style.width = '80%';
        content.style.height = '80%';
        content.style.border = 'none';
        modal.appendChild(content);

        modal.addEventListener('click', e => {
            if (e.target === modal) modal.remove();
        });

        document.body.appendChild(modal);
    }
}

/**
 * Initialisation automatique sur tous les formulaires avec data-fetch-form
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-fetch-form]').forEach(form => new FetchForm(form));
});