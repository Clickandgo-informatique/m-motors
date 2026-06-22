export default class Multiselect {
  constructor(wrapper) {
    this.wrapper = wrapper;
    this.url = wrapper.dataset.url;

    this.selectedItems = [];
    this.availableItems = [];
  }
  createMultiselect() {
    console.log("classe multiselect initialisée");

    // div pour les éléments sélectionnés
    const selectedItemsDiv = document.createElement("div");
    this.wrapper.appendChild(selectedItemsDiv);

    //div pour la liste des éléments disponibles
    const avalaibleItems = document.createElement("div");
    this.wrapper.appendChild(avalaibleItems);
  }

  async fetchData() {
    const response = await fetch(this.url);
    const items = await response.json();

    console.log(items);
  }

  initMultiselect() {
    this.createMultiselect();
    await this.fetchData();
    wrapper.dataset.multiselectInitialized = "1";
  }
}
