export default class Multiselect {
    constructor(wrapper) {
        this.wrapper = wrapper;
        this.url = wrapper.dataset.url;

        this.selectedItems = [];
        this.availableItems = [];

        this.hiddenInput = document.querySelector("#vehicle_model_features");

        this.selectedItemsDiv = null;
        this.availableItemsDiv = null;
        this.searchInput = null;
    }

    createMultiselect() {
        this.selectedItemsDiv = document.createElement("div");
        this.selectedItemsDiv.classList.add("multiselect-selected");

        this.availableItemsDiv = document.createElement("div");
        this.availableItemsDiv.classList.add("multiselect-available");

        this.searchInput = document.createElement("input");
        this.searchInput.type = "text";
        this.searchInput.placeholder = "rechercher...";
        this.searchInput.classList.add("multiselect-search");

        this.wrapper.appendChild(this.selectedItemsDiv);
        this.wrapper.appendChild(this.availableItemsDiv);
        this.availableItemsDiv.appendChild(this.searchInput);

        this.searchInput.addEventListener("input", () => {
            this.renderAvailable(this.searchInput.value);
        });
    }

    async fetchData() {
        const response = await fetch(this.url);

        if (!response.ok) {
            throw new Error("impossible de récupérer les données du multiselect");
        }

        const data = await response.json();

        this.selectedItems = data.selected || [];
        this.availableItems = data.available || [];
    }

    renderAvailable(query = "") {
        const oldUl = this.availableItemsDiv.querySelector("ul");
        if (oldUl) oldUl.remove();

        const ul = document.createElement("ul");
        ul.classList.add("list-wrapper");

        const filtered = this.availableItems.filter(item =>
            item.label.toLowerCase().includes(query.toLowerCase())
        );

        for (const item of filtered) {
            const li = document.createElement("li");

            li.dataset.id = item.id;
            li.textContent = item.label;
            li.classList.add("available-item");

            li.addEventListener("click", () => {
                this.selectItem(item.id);
            });

            ul.appendChild(li);
        }

        this.availableItemsDiv.appendChild(ul);
    }

    renderSelected() {
        this.selectedItemsDiv.innerHTML = "";

        const container = document.createElement("div");
        container.classList.add("chips-wrapper");

        for (const item of this.selectedItems) {
            const chip = document.createElement("div");
            chip.classList.add("chip");

            const label = document.createElement("span");
            label.textContent = item.label;

            const remove = document.createElement("button");
            remove.type = "button";
            remove.classList.add("chip-remove");
            remove.textContent = "×";

            remove.addEventListener("click", () => {
                this.unselectItem(item.id);
            });

            chip.appendChild(label);
            chip.appendChild(remove);
            container.appendChild(chip);
        }

        this.selectedItemsDiv.appendChild(container);
    }

    selectItem(id) {
        const index = this.availableItems.findIndex(i => i.id === id);
        if (index === -1) return;

        const item = this.availableItems.splice(index, 1)[0];
        this.selectedItems.push(item);

        this.refresh();
    }

    unselectItem(id) {
        const index = this.selectedItems.findIndex(i => i.id === id);
        if (index === -1) return;

        const item = this.selectedItems.splice(index, 1)[0];
        this.availableItems.push(item);

        this.refresh();
    }

    updateHiddenInput() {
        if (!this.hiddenInput) return;

        this.hiddenInput.value = this.selectedItems.map(i => i.id).join(",");
    }

    refresh() {
        this.renderAvailable(this.searchInput?.value || "");
        this.renderSelected();
        this.updateHiddenInput();
    }

    async initMultiselect() {
        this.createMultiselect();
        await this.fetchData();
        this.refresh();
    }
}

export function initMultiselect(root = document) {
    root.querySelectorAll("[data-multiselect]").forEach(wrapper => {
        if (wrapper.dataset.multiselectInitialized === "1") return;

        wrapper.dataset.multiselectInitialized = "1";

        const multiselect = new Multiselect(wrapper);
        multiselect.initMultiselect();
    });
}
