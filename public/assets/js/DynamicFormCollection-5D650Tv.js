export default class DynamicFormCollection {
    constructor(root) {
        if (!root) return;

        this.root = root;
        this.wrapper = root.querySelector("[data-collection-wrapper]");
        this.addButton = root.querySelector("[data-collection-add]");
        this.endpoint = root.dataset.collectionEndpoint;
        this.itemSelector =
            root.dataset.collectionItemSelector || ".definition-item";
        this.deleteSelector =
            root.dataset.collectionDeleteSelector || "[data-collection-delete]";
        this.animate = root.dataset.collectionAnimate !== "false";

        this.index = this.wrapper.querySelectorAll(this.itemSelector).length;

        this.addButton.addEventListener("click", () => {
            this.addItem();
        });

        this.bindDeleteButtons();
    }

    async addItem() {
        // 🔥 Si la langue n’est pas encore choisie
        if (!this.endpoint) {
            alert(
                "Veuillez d'abord choisir une langue avant d'ajouter une définition.",
            );
            return;
        }

        // 🔥 Remplace le dernier segment numérique par l’index
        const url = this.endpoint.replace(/\/\d+$/, `/${this.index}`);

        try {
            const response = await fetch(url);
            const html = await response.text();

            const temp = document.createElement("div");
            temp.innerHTML = html.trim();
            const newItem = temp.firstElementChild;

            if (this.animate) {
                newItem.style.opacity = "0";
                newItem.style.transition = "opacity .3s";
            }

            this.wrapper.appendChild(newItem);
            this.index++;

            this.ensureDeleteButton(newItem);
            this.bindDeleteButtons();

            if (this.animate) {
                requestAnimationFrame(() => (newItem.style.opacity = "1"));
            }
        } catch (e) {
            console.error("Erreur lors du fetch :", e);
        }
    }

    bindDeleteButtons() {
        this.wrapper.querySelectorAll(this.deleteSelector).forEach((btn) => {
            if (!btn.dataset.bound) {
                btn.dataset.bound = "true";
                btn.addEventListener("click", (e) => this.deleteItem(e));
            }
        });
    }

    ensureDeleteButton(item) {
        if (!item.querySelector(this.deleteSelector)) {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.textContent = "Supprimer";
            btn.setAttribute("data-collection-delete", "");
            item.appendChild(btn);
        }
    }

    deleteItem(event) {
        event.preventDefault();

        if (!confirm("Voulez-vous vraiment supprimer cet élément ?")) return;

        const item = event.target.closest(this.itemSelector);
        if (!item) return;

        if (this.animate) {
            item.style.opacity = "0";
            setTimeout(() => {
                item.remove();
                this.reindex();
            }, 300);
        } else {
            item.remove();
            this.reindex();
        }
    }

    reindex() {
        const items = this.wrapper.querySelectorAll(this.itemSelector);
        this.index = items.length;

        items.forEach((item, i) => {
            item.querySelectorAll("[name]").forEach((field) => {
                field.name = field.name.replace(/\[\d+]/, `[${i}]`);
            });

            item.querySelectorAll("[id]").forEach((field) => {
                field.id = field.id.replace(/_\d+_/, `_${i}_`);
            });
        });
    }
}
