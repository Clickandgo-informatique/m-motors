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
    // ouverture des modales ajax
    document.body.addEventListener("click", e => {
      // fermeture prioritaire de la modale
      if (e.target.closest("[data-modal-close]")) {
        this.closeModal();
        return;
      }

      const trigger = e.target.closest(
        "[data-ajax-modal], form[data-ajax-modal], a[data-ajax-modal], .vehicle-card"
      );

      if (!trigger) {
        return;
      }

      // empêche l'ouverture de la modale lors d'un clic
      // sur certaines actions internes de la carte véhicule
      if (e.target.closest(".vehicle-card-actions, .favorite-btn")) {
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

    // fermeture via touche escape
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

    // priorité à data-url
    if (trigger.dataset?.url && trigger.dataset.url.trim() !== "") {
      return trigger.dataset.url;
    }

    // formulaire ajax
    if (trigger instanceof HTMLFormElement) {
      return trigger.action;
    }

    // lien ajax
    if (trigger instanceof HTMLAnchorElement) {
      return trigger.href;
    }

    // fallback éventuel
    if (trigger.dataset?.action && trigger.dataset.action.trim() !== "") {
      return trigger.dataset.action;
    }

    console.warn("[AjaxManager] aucun resolver pour", trigger);

    return null;
  }

  async loadModal(url) {
    // évite les doubles requêtes simultanées
    if (this.isLoading) {
      return;
    }

    this.isLoading = true;

    // ouverture immédiate de la modale
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

      // injection du contenu html
      this.modalBody.innerHTML = html;

      // initialisation des composants internes
      this.initModalComponents();
    } catch (error) {
      console.error("[AjaxManager] erreur", error);

      this.modalBody.innerHTML = "Erreur de chargement";
    } finally {
      this.isLoading = false;
    }
  }

  closeModal() {
    // fermeture visuelle
    this.modal.classList.remove("open");

    // nettoyage du contenu
    this.modalBody.innerHTML = "";
  }

  initModalComponents() {
    this.initAutocomplete();
    this.initImageZoom();
    this.initDropzones();
    initMultiselect(this.modalBody);
  }

  // Gestion des autocomplete
  initAutocomplete() {
    const inputs = this.modalBody.querySelectorAll("input[data-url]");

    inputs.forEach(input => {
      // évite une double initialisation
      if (input.dataset.autocompleteInit === "1") {
        return;
      }

      new Autocomplete(input);

      input.dataset.autocompleteInit = "1";
    });
  }

  // Gestion des images
  initImageZoom() {
    const overlay = this.modalBody.querySelector("#image-zoom-overlay");
    const target = this.modalBody.querySelector("#image-zoom-target");
    const closeBtn = this.modalBody.querySelector(".image-zoom-close");

    if (!overlay || !target) {
      return;
    }

    const images = this.modalBody.querySelectorAll(".vehicle-gallery img, .vehicle-thumb");

    const closeZoom = () => {
      overlay.classList.remove("open");
      target.src = "";
    };

    // ouverture du zoom image
    images.forEach(img => {
      img.addEventListener("click", () => {
        target.src = img.src;

        overlay.classList.add("open");
      });
    });

    // fermeture via bouton
    if (closeBtn) {
      closeBtn.addEventListener("click", closeZoom);
    }

    // fermeture via clic overlay
    overlay.addEventListener("click", e => {
      // évite la fermeture si clic direct sur l'image
      if (e.target === target) {
        return;
      }

      closeZoom();
    });

    // fermeture via escape
    document.addEventListener("keydown", e => {
      if (e.key === "Escape") {
        closeZoom();
      }
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
