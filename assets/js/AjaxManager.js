// assets/js/AjaxManager.js

export default class AjaxManager {
    constructor(options = {}) {
        this.modalSelector = options.modalSelector || "#modal";
        this.modalBody = document.querySelector("#modal-body");

        this.modalContentSelector =
            options.modalContentSelector || "#modal-content";

        this.modal = document.querySelector(this.modalSelector);
        this.modalContent = document.querySelector(this.modalContentSelector);

        this.bindEvents();
    }

    bindEvents() {
        // Ouverture modale via lien AJAX
        document.addEventListener("click", (e) => {
            const link = e.target.closest("[data-ajax-modal]");
            if (link) {
                e.preventDefault();
                this.loadModal(link.href);
            }
        });

        // Soumission AJAX d’un formulaire
        document.addEventListener("submit", (e) => {
            const form = e.target.closest("[data-ajax-form]");
            if (form) {
                e.preventDefault();
                this.submitForm(form);
            }
        });

        // Bouton de fermeture
        document.addEventListener("click", (e) => {
            if (e.target.matches("[data-modal-close]")) {
                this.closeModal();
            }
        });

        // Data-collection (ajout / suppression)
        document.addEventListener("click", (e) => {
            if (e.target.matches("[data-collection-add]")) {
                this.addCollectionItem(e.target);
            }
            if (e.target.matches("[data-collection-remove]")) {
                this.removeCollectionItem(e.target);
            }
        });
    }

    async loadModal(url) {
        const response = await fetch(url);
        const html = await response.text();

        this.modalBody.innerHTML = html;
        this.modal.classList.add("open");
    }

    async submitForm(form) {
        const response = await fetch(form.action, {
            method: form.method,
            body: new FormData(form),
        });

        const html = await response.text();

        if (response.ok) {
            // Mise à jour d’un élément du DOM
            const target = form.dataset.updateTarget;
            if (target) {
                document.querySelector(target).outerHTML = html;
            }
            this.closeModal();
        } else {
            // Réafficher le formulaire avec erreurs
            this.modalBody.innerHTML = html;
        }
    }

    closeModal() {
        // Ajoute la classe qui déclenche l’animation
        this.modal.classList.add("closing");

        // Attend la fin de l’animation
        setTimeout(() => {
            this.modal.classList.remove("open", "closing");
            this.modalBody.innerHTML = "";
        }, 250); // durée identique à l’animation CSS
    }

    addCollectionItem(button) {
        const container = document.querySelector(button.dataset.collectionAdd);
        const prototype = container.dataset.prototype;
        const index = container.children.length;

        const newItem = prototype.replace(/__name__/g, index);
        container.insertAdjacentHTML("beforeend", newItem);
    }

    removeCollectionItem(button) {
        button.closest("[data-collection-item]").remove();
    }
}
document.addEventListener("click", async (e) => {
    const link = e.target.closest("[data-ajax-delete]");
    if (!link) return;

    e.preventDefault();

    const url = link.href;
    const token = link.dataset.deleteToken;
    const target = link.dataset.deleteTarget;

    if (!confirm("Supprimer cet élément ?")) {
        return;
    }

    const response = await fetch(url, {
        method: "POST",
        body: new URLSearchParams({ _token: token }),
    });

    const id = await response.text();

    document.querySelector(target).remove();
});
