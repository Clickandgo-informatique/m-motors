export default class Multiselect {
  constructor(options, wrapper, url) {
    this.wrapper = document.querySelector("#multiselect-wrapper");
    this.url = document.querySelector("[data-url]");
  }

  createMultiselect() {
    // div pour les éléments sélectionnés
    const selectedItemsDiv = document.createElement("div");
    this.wrapper.appendChild("selectedItemsDiv");

    //div pour la liste des éléments disponibles
    const avalaibleItems = document.createElement("div");
    this.wrapper.appendChild("avalaibleItems");
  }

  async fetchData() {
    const response = await fetch(this.url);
    const items = await response.json();

    console.log(items);
  }

  init() {
    this.createMultiselect();
    this.fetchData();
  }
}
init();
