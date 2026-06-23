export default class Multiselect {
  constructor(wrapper) {
    this.wrapper = wrapper;
    this.url = wrapper.dataset.url;

    this.selectedItems = [];
    this.availableItems = [];

    this.hiddenInput = document.querySelector("#vehicle_model_features");

    this.selectedItemsDiv = null;
    this.availableItemsDiv = null;
  }

  // création de la structure html du multiselect
  createMultiselect() {
    this.selectedItemsDiv = document.createElement("div");
    this.selectedItemsDiv.classList.add("multiselect-selected");

    this.availableItemsDiv = document.createElement("div");
    this.availableItemsDiv.classList.add("multiselect-available");

    this.wrapper.appendChild(this.selectedItemsDiv);
    this.wrapper.appendChild(this.availableItemsDiv);
  }

  // récupération des données
  async fetchData() {
    const response = await fetch(this.url);

    if (!response.ok) {
      throw new Error("Impossible de récupérer les données du multiselect.");
    }

    const data = await response.json();

    this.selectedItems = data.selected || [];
    this.availableItems = data.available || [];
  }

  // affichage des éléments disponibles
  fillAvailableItems() {
    this.availableItemsDiv.innerHTML = "";

    const ul = document.createElement("ul");
    ul.classList.add("list-wrapper");

    for (const item of this.availableItems) {
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

  // affichage des éléments sélectionnés
  fillSelectedItems() {
    this.selectedItemsDiv.innerHTML = "";

    const ul = document.createElement("ul");
    ul.classList.add("list-wrapper");

    for (const item of this.selectedItems) {
      const li = document.createElement("li");

      li.dataset.id = item.id;
      li.textContent = item.label;
      li.classList.add("selected-item");

      li.addEventListener("click", () => {
        this.unselectItem(item.id);
      });

      ul.appendChild(li);
    }

    this.selectedItemsDiv.appendChild(ul);
  }

  // sélection d’un item
  selectItem(id) {
    const index = this.availableItems.findIndex(i => i.id === id);
    if (index === -1) return;

    const item = this.availableItems.splice(index, 1)[0];
    this.selectedItems.push(item);

    this.refresh();
  }

  // désélection d’un item
  unselectItem(id) {
    const index = this.selectedItems.findIndex(i => i.id === id);
    if (index === -1) return;

    const item = this.selectedItems.splice(index, 1)[0];
    this.availableItems.push(item);

    this.refresh();
  }

  // actualisation du champ caché
  updateHiddenInput() {
    if (!this.hiddenInput) {
      return;
    }

    this.hiddenInput.value = this.selectedItems.map(item => item.id).join(",");
  }

  // refresh global UI
  refresh() {
    this.fillAvailableItems();
    this.fillSelectedItems();
    this.updateHiddenInput();
  }

  // initialisation du composant
  async initMultiselect() {
    this.createMultiselect();

    await this.fetchData();

    this.refresh();
  }
}

// initialisation de tous les multiselects présents dans un conteneur
export function initMultiselect(root = document) {
  root.querySelectorAll("[data-multiselect]").forEach(wrapper => {
    if (wrapper.dataset.multiselectInitialized === "1") {
      return;
    }

    wrapper.dataset.multiselectInitialized = "1";

    const multiselect = new Multiselect(wrapper);
    multiselect.initMultiselect();
  });
}
