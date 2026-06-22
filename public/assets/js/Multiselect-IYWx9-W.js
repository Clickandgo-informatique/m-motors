console.log("classe multiselect initialisée");
export default class Multiselect {
  constructor(wrapper) {
    this.wrapper = wrapper;
    this.url = wrapper.dataset.url;

    this.selectedItems = [];
    this.availableItems = [];
  }

  // création de la structure HTML du multiselect
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

    this.availableItems = await response.json();

    console.log(this.availableItems);
  }

  // initialisation du composant
  async initMultiselect() {
    this.createMultiselect();

    await this.fetchData();

    this.wrapper.dataset.multiselectInitialized = "1";
  }
}
