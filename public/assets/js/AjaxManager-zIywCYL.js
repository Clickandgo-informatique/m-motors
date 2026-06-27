import Dropzone from "./Dropzone.js";
import Autocomplete from "./Autocomplete.js";
import { initMultiselect } from "./Multiselect.js";
import { initDossierFinancingToggle } from "./dossier-financing-toggle.js";

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
        if (!trigger) {
            return null;
        }

        if (trigger.dataset?.url && trigger.dataset.url.trim() !== "") {
            return trigger.dataset.url;
        }

        if (trigger instanceof HTMLFormElement) {
            return trigger.action;
        }

        if (trigger instanceof HTMLAnchorElement) {
            return trigger.href;
        }

        if (trigger.dataset?.action && trigger.dataset.action.trim() !== "") {
            return trigger.dataset.action;
        }

        return null;
    }

    async loadModal(url) {
        if (this.isLoading) {
            return;
        }

        this.isLoading = true;

        this.modal.classList.add("open");
        this.modalBody.innerHTML = "Chargement...";

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
            this.modalBody.innerHTML = "Erreur de chargement";
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
        this.initImageZoom();
        this.initDropzones();

        initMultiselect(this.modalBody);
        initDossierFinancingToggle(this.modalBody);
    }

    initAutocomplete() {
        const inputs = this.modalBody.querySelectorAll("input[data-url]");

        inputs.forEach(input => {
            if (input.dataset.autocompleteInit === "1") {
                return;
            }

            new Autocomplete(input);
            input.dataset.autocompleteInit = "1";
        });
    }

    initImageZoom() {
        const overlay = document.querySelector("#image-zoom-overlay");
        const target = document.querySelector("#image-zoom-target");
        const closeBtn = document.querySelector(".image-zoom-close");

        if (!overlay || !target) {
            return;
        }

        const images = document.querySelectorAll(".vehicle-gallery img, .vehicle-thumb");

        const closeZoom = () => {
            overlay.classList.remove("is-active");
            target.src = "";
        };

        images.forEach(img => {
            img.addEventListener("click", () => {
                target.src = img.src;
                overlay.classList.add("is-active");
            });
        });

        if (closeBtn) {
            closeBtn.addEventListener("click", closeZoom);
        }

        overlay.addEventListener("click", e => {
            if (e.target === target) {
                return;
            }
            closeZoom();
        });

        document.addEventListener("keydown", e => {
            if (e.key === "Escape") {
                closeZoom();
            }
        });
    }

    initDropzones() {
        const zones = this.modalBody.querySelectorAll(".dropzone");

        zones.forEach(el => {
            if (el.dataset.dropzoneInitialized === "1") {
                return;
            }

            el.dataset.dropzoneInitialized = "1";
            new Dropzone(el);
        });
    }
}
