import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";

import initSidebar from "./js/sidebar.js";
import initDoubleSlider from "./js/rangeSelector.js";
import Dropzone from "./js/Dropzone.js";
import DynamicFormCollection from "./js/DynamicFormCollection.js";
import FetchForm from "./js/FetchForm.js";
import AjaxManager from "./js/AjaxManager.js";
import ToggleVehicleFavorite from "./js/ToggleVehicleFavorite.js";
import Autocomplete from "./js/Autocomplete.js";
import EventBus from "./js/EventBus.js";
import FilterBadges from "./js/FilterBadges.js";
import initFiltersDrawer from "./js/sidebar.js";
import "./js/cookie-banner.js";
import Multiselect from "./js/Multiselect.js";
import "./js/crm-search.js";
import "./js/dossier-financing-toggle.js";
import { initVehicleUsageTypeConfirmation } from "./js/usage-type-confirmation.js";

window.Autocomplete = Autocomplete;

console.log("app.js initialisé");

/**
 * RESET FLAGS
 */
function resetInitFlags(root) {
    root.querySelectorAll("[data-multiselect-initialized]").forEach(el => {
        el.removeAttribute("data-multiselect-initialized");
    });

    root.querySelectorAll("[data-fetch-form-initialized]").forEach(el => {
        el.removeAttribute("data-fetch-form-initialized");
    });

    root.querySelectorAll("[data-autocomplete-initialized]").forEach(el => {
        el.removeAttribute("data-autocomplete-initialized");
    });

    root.querySelectorAll("[data-slider-initialized]").forEach(el => {
        el.removeAttribute("data-slider-initialized");
    });
}

/**
 * DROPZONES
 */
function initDropzones(root = document) {
    root.querySelectorAll(".dropzone").forEach(el => {
        if (el.dataset.dropzoneInitialized === "1") return;
        el.dataset.dropzoneInitialized = "1";
        new Dropzone(el);
    });
}

/**
 * FAVORITES
 */
function initFavorites(root = document) {
    root.querySelectorAll('[data-action="toggle-favorite"]').forEach(btn => {
        if (btn.dataset.favoriteInitialized === "1") return;
        btn.dataset.favoriteInitialized = "1";
        new ToggleVehicleFavorite(btn);
    });
}

/**
 * AUTOCOMPLETE
 */
function initAutocomplete(root = document) {
    root.querySelectorAll("[data-module='autocomplete']").forEach(input => {
        if (input.dataset.autocompleteInitialized === "1") return;
        input.dataset.autocompleteInitialized = "1";
        new Autocomplete(input);
    });
}

/**
 * FETCH FORMS
 */
function initFetchForms(root = document) {
    root.querySelectorAll("[data-module='fetch-form']").forEach(form => {
        if (form.dataset.fetchFormInitialized === "1") return;
        form.dataset.fetchFormInitialized = "1";
        new FetchForm(form);
    });
}

/**
 * COLLECTIONS
 */
function initCollections(root = document) {
    root.querySelectorAll("[data-collection]").forEach(el => {
        if (el.dataset.collectionInitialized === "1") return;
        el.dataset.collectionInitialized = "1";
        new DynamicFormCollection(el);
    });
}

/**
 * SLIDERS
 */
function initSliders(root = document) {
    root.querySelectorAll(".double-slider").forEach(slider => {
        slider.dataset.sliderInitialized = "0";
        initDoubleSlider(slider);
    });
}

/**
 * BADGES (FIX IMPORTANT)
 */
function initBadges(root = document) {
    const container = root.querySelector("#filters-summary");
    const form = root.querySelector("[data-module='fetch-form']");

    if (!container || !form) return;

    window.__filterBadges = new FilterBadges(container, form, () => {
        form.dispatchEvent(new Event("change", { bubbles: true }));
    });

    window.__filterBadges.updateBadges();
}

/**
 * AJAX MANAGER
 */
function initAjaxManager() {
    if (!window.ajaxManager) {
        window.ajaxManager = new AjaxManager();
    }
}

/**
 * PAGINATION
 */
function initPagination() {
    document.addEventListener("click", e => {
        const link = e.target.closest("[data-page]");
        if (!link) return;

        e.preventDefault();

        const form = document.querySelector("[data-module='fetch-form']");
        if (!form) return;

        let input = form.querySelector("input[name='page']");

        if (!input) {
            input = document.createElement("input");
            input.type = "hidden";
            input.name = "page";
            form.appendChild(input);
        }

        input.value = link.dataset.page;

        form.dispatchEvent(new Event("change", { bubbles: true }));
    });
}

/**
 * MULTISELECT
 */
function initMultiselect(root = document) {
    root.querySelectorAll("[data-multiselect]").forEach(wrapper => {
        if (wrapper.dataset.multiselectInitialized === "1") return;
        wrapper.dataset.multiselectInitialized = "1";

        const multiselect = new Multiselect(wrapper);
        multiselect.initMultiselect();
    });
}

/**
 * INIT APP
 */
function initApp() {
    initSidebar?.();
    initDropzones();
    initFavorites();
    initAutocomplete();
    initFetchForms();
    initCollections();
    initAjaxManager();
    initSliders();
    initPagination();
    initBadges();
    initFiltersDrawer();
    initMultiselect();
}

document.addEventListener("DOMContentLoaded", initApp);

/**
 * UI UPDATE PIPELINE
 */
EventBus.on("ui:updated", ({ target }) => {
    const root = target || document;

    resetInitFlags(root);

    initAutocomplete(root);
    initFetchForms(root);
    initFavorites(root);
    initCollections(root);
    initSliders(root);
    initMultiselect(root);

    initBadges(root);
});

/**
 * DEBUG
 */
document.addEventListener("change", e => {
    if (e.target.closest("#filters-form")) {
        console.log("[DEBUG] CHANGE EVENT:", e.target);
    }
});
