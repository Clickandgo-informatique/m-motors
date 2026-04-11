// assets/js/FetchForm.js

/**
 * Classe FetchForm
 * ----------------
 * Gère les formulaires AJAX / autocomplete avec options configurables via data-attributes
 * - resultDiv : div où injecter les résultats
 * - resultLinks : si true, transforme les résultats en liens cliquables
 * - itemUrl : URL modèle pour les items avec ID_PLACEHOLDER
 * - ajaxModal : ouvre les résultats en modal si défini
 * - itemClass / linkClass / noResultsClass : classes CSS pour la génération dynamique
 * - pagination, paginationMode, paginationLimit : options de pagination
 * - highlight, defaultSuggestions : options d'autocomplete
 */

export default class FetchForm {
  constructor(input) {
    if (!input) return;

    this.input = input;

    // --- Lire les dataset ---
    const dataset = input.dataset || {};

    this.resultDivId = dataset.resultDiv || 'results';
    this.resultDiv = document.getElementById(this.resultDivId);
    if (!this.resultDiv) {
      console.warn(`[FetchForm] Container #${this.resultDivId} introuvable`);
      return;
    }

    this.url = dataset.fetchUrl || input.form?.action || '';
    this.itemUrl = dataset.itemUrl || '';
    this.resultLinks = dataset.resultLinks === 'true';
    this.ajaxModal = dataset.ajaxModal === 'true';
    this.itemClass = dataset.itemClass || 'item';
    this.linkClass = dataset.linkClass || 'link';
    this.noResultsClass = dataset.noResultsClass || 'no-results';
    this.pagination = dataset.pagination === 'ajax';
    this.paginationMode = dataset.paginationMode || 'load-more';
    this.paginationLimit = parseInt(dataset.paginationLimit) || 10;
    this.highlight = dataset.highlight === 'true';
    this.defaultSuggestions = dataset.defaultSuggestions === 'true';

    // --- Debounce ---
    this.debounceDelay = 300;
    this.timeout = null;

    // --- Bind des événements ---
    this.input.addEventListener('input', (e) => this.onInput(e));
    this.input.addEventListener('keydown', (e) => this.onKeyDown(e));

    // --- Initialisation éventuelle de suggestions par défaut ---
    if (this.defaultSuggestions) this.fetchResults('');

    console.log(`[FetchForm] Initialisé pour #${this.input.name}`);
  }

  onInput(e) {
    clearTimeout(this.timeout);
    const value = e.target.value.trim();

    this.timeout = setTimeout(() => {
      this.fetchResults(value);
    }, this.debounceDelay);
  }

  onKeyDown(e) {
    // Ici on pourrait gérer les touches flèches / Enter pour autocomplete
  }

  async fetchResults(query) {
    if (!this.url) return;

    try {
      const params = new URLSearchParams({ [this.input.name]: query });
      const response = await fetch(`${this.url}?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      if (!response.ok) throw new Error(`Erreur AJAX: ${response.status}`);

      const html = await response.text();
      this.renderResults(html);
    } catch (err) {
      console.error('[FetchForm] Erreur fetchResults:', err);
    }
  }

  renderResults(html) {
    if (!this.resultDiv) return;

    // --- Si pas de résultats ---
    if (!html || html.trim() === '') {
      this.resultDiv.innerHTML = `<div class="${this.noResultsClass}">Aucun résultat</div>`;
      return;
    }

    // --- Injection du HTML ---
    this.resultDiv.innerHTML = html;

    // --- Transformation en liens si demandé ---
    if (this.resultLinks && this.itemUrl) {
      this.resultDiv.querySelectorAll(`.${this.itemClass}`).forEach(el => {
        const id = el.dataset.id;
        if (!id) return;

        let link = document.createElement('a');
        link.href = this.itemUrl.replace('ID_PLACEHOLDER', id);
        link.className = this.linkClass;
        link.innerHTML = el.innerHTML;

        // --- Option modal ---
        if (this.ajaxModal) link.dataset.ajaxModal = 'true';

        el.innerHTML = '';
        el.appendChild(link);
      });
    }
  }
}