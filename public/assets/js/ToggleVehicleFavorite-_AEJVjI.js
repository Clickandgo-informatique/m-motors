export default class ToggleVehicleFavorite {
  constructor(elementOrSelector = '[data-action="toggle-favorite"]') {
    // Support : un seul bouton ou plusieurs
    if (elementOrSelector instanceof HTMLElement) {
      this.buttons = [elementOrSelector];
    } else {
      this.buttons = document.querySelectorAll(elementOrSelector);
    }

    if (!this.buttons.length) return;

    this.init();
  }

  init() {
    this.buttons.forEach(button => {
      button.addEventListener("click", e => this.handleClick(e, button));
    });
  }

  async handleClick(event, button) {
    event.preventDefault();

    // Anti double clic
    if (button.dataset.loading === "true") return;
    button.dataset.loading = "true";

    const url = button.dataset.url;
    if (!url) {
      console.error("Missing data-url on favorite button");
      button.dataset.loading = "false";
      return;
    }

    const isActive = button.classList.contains("is-favorite");

    // UI optimiste (instant)
    this.toggleUI(button, !isActive);

    try {
      const response = await fetch(url, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!response.ok) {
        throw new Error("HTTP error");
      }

      const data = await response.json();

      // Synchronisation avec backend
      this.toggleUI(button, data.added);
    } catch (error) {
      console.error("Favorite toggle error:", error);

      // Rollback UI
      this.toggleUI(button, isActive);
    }

    button.dataset.loading = "false";
  }

  toggleUI(button, isFavorite) {
    // Classe principale
    button.classList.toggle("is-favorite", isFavorite);

    // Icône FontAwesome
    const icon = button.querySelector("[data-icon]");
    if (icon) {
      icon.classList.toggle("fa-regular", !isFavorite);
      icon.classList.toggle("fa-solid", isFavorite);

      // Animation (retrigger propre)
      icon.classList.remove("animate");
      void icon.offsetWidth; // force reflow
      icon.classList.add("animate");
    }

    // Accessibilité
    button.setAttribute("aria-pressed", isFavorite ? "true" : "false");
  }
}
