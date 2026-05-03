import FetchForm from "./js/FetchForm.js";
import Autocomplete from "./js/Autocomplete.js";
import AjaxManager from "./js/AjaxManager.js";
import EventBus from "./js/EventBus.js";
import "./stimulus_bootstrap.js";
import "./js/theme.js";
import "./styles/app.css";

function initFetchForms() {
  document.querySelectorAll("[data-module='fetch-form']").forEach(form => {
    if (form.dataset.initialized) return;

    form.dataset.initialized = "1";
    new FetchForm(form);
  });
}

function initAutocomplete() {
  document.querySelectorAll("[data-autocomplete='true']").forEach(input => {
    if (input.dataset.initialized) return;

    input.dataset.initialized = "1";
    new Autocomplete(input);
  });
}

function initAjaxManager() {
  if (!window.ajaxManager) {
    window.ajaxManager = new AjaxManager();
  }
}

function init() {
  initFetchForms();
  initAutocomplete();
  initAjaxManager();
}

document.addEventListener("DOMContentLoaded", init);

EventBus.on("ui:updated", () => {
  initFetchForms();
  initAutocomplete();
});
