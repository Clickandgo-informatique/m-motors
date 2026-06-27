import Dropzone from "./Dropzone.js";
import Autocomplete from "./Autocomplete.js";
import { initMultiselect } from "./Multiselect.js";

export default class AjaxManager {
    constructor() {
        console.log("ajaxmanager.js initialisé");

        this.modal = document.querySelector("#modal");
        this.modalBody = document.querySelector("#modal-body");

        if (!this.modal || !this.modalBody) {
            console.warn("[AjaxManager] modal absente");
            return;
        }

        this.isLoading = false;

        this.bindEvents();
    }

    bindEvents() {
        document.body.addEventListener("click", e => {
            const closeBtn = e.target.closest("[data-modal-close]");
            if (closeBtn) {
                this.closeModal();
                return;
            }

            if (e.target.closest(".favorite-btn")) {
                return;
            }

            const trigger = e.target.closest(
                "[data-ajax-modal], form[data-ajax-modal], a[data-ajax-modal]"
            );

            if (!trigger) {
                return;
            }

            e.preventDefault();

            const url = this.resolveUrl(trigger);

            if (!url) {
                console.error("[AjaxManager] url manquante", trigger);
                return;
            }

            this.loadModal(url);
        });

        document.addEventListener("keydown", e => {
            if (e.key === "Escape") {
                this.closeModal();
            }
        });
    }

    resolveUrl(trigger) {
        if (!trigger) return null;

        if (trigger.dataset?.url) return trigger.dataset.url;
        if (trigger instanceof HTMLFormElement) return trigger.action;
        if (trigger instanceof HTMLAnchorElement) return trigger.href;
        if (trigger.dataset?.action) return trigger.dataset.action;

        console.warn("[AjaxManager] aucun resolver pour", trigger);
        return null;
    }

    async loadModal(url) {
        if (this.isLoading) return;

        this.isLoading = true;

        this.modal.classList.add("open");
        this.modalBody.innerHTML = "chargement...";

        try {
            const response = await fetch(url, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const html = await response.text();
            this.modalBody.innerHTML = html;

            this.initModalComponents();
        } catch (error) {
            console.error("[AjaxManager] erreur", error);
            this.modalBody.innerHTML = "erreur de chargement";
        } finally {
            this.isLoading = false;
        }
    }

    closeModal() {
        this.modal.classList.remove("open");
        this.modalBody.innerHTML = "";
    }

    initModalComponents() {
        this.initAutocomplete();
        this.initDropzones();
        initMultiselect(this.modalBody);

        this.initCrmComponents();
    }

    initCrmComponents() {
        if (window.initDossierFinancingToggle) {
            window.initDossierFinancingToggle(this.modalBody);
        }

        this.initDossierTypeToggle(this.modalBody);
    }

    initDossierTypeToggle(root) {
        const typeSelect = root.querySelector('[id$="_type"]');
        const financingCard = root.querySelector("#financing-wrapper");

        if (!typeSelect || !financingCard) return;

        function toggle() {
            financingCard.style.display = "block";
        }

        typeSelect.addEventListener("change", toggle);
        toggle();
    }

    initAutocomplete() {
        const inputs = this.modalBody.querySelectorAll("input[data-url]");

        inputs.forEach(input => {
            if (input.dataset.autocompleteInit === "1") return;

            new Autocomplete(input);
            input.dataset.autocompleteInit = "1";
        });
    }

    initDropzones() {
        const zones = this.modalBody.querySelectorAll(".dropzone");

        zones.forEach(el => {
            if (el.dataset.dropzoneInitialized === "1") return;

            el.dataset.dropzoneInitialized = "1";
            new Dropzone(el);
        });
    }
}
