console.log("classe multiselect initialisée");

export default class Multiselect {
  constructor(wrapper) {
    this.wrapper = wrapper;
    this.url = wrapper.dataset.url;

    console.log("url =", this.url);

    this.selectedItems = [];
    this.availableItems = [];
  }

  // création de la structure html du multiselect
  createMultiselect() {
    // conteneur des éléments sélectionnés
    this.selectedItemsDiv = document.createElement("div");
    this.selectedItemsDiv.classList.add("multiselect-selected");

    // conteneur des éléments disponibles
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

    this.selectedItems = data.selected;
    this.availableItems = data.available;

    console.log(this.availableItems);
    console.log(this.selectedItems);
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

      ul.appendChild(li);
    }

    this.availableItemsDiv.appendChild(ul);
  }

  // initialisation du composant
  async initMultiselect() {
    this.createMultiselect();

    await this.fetchData();

    this.fillAvailableItems();
  }
}

// initialisation de tous les multiselects présents dans un conteneur
export function initMultiselect(root = document) {
  root.querySelectorAll("[data-multiselect]").forEach(wrapper => {
    // évite une double initialisation
    if (wrapper.dataset.multiselectInitialized === "1") {
      return;
    }

    wrapper.dataset.multiselectInitialized = "1";

    const multiselect = new Multiselect(wrapper);

    multiselect.initMultiselect();
  });
}