export default class ToggleVehicleFavorite {
  constructor(selector = '[data-action="toggle-favorite"]') {
    this.buttons = document.querySelectorAll(selector);

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

    // 🔒 Anti double clic
    if (button.dataset.loading === "true") return;
    button.dataset.loading = "true";

    const url = button.dataset.url;
    if (!url) {
      console.error("Missing data-url on favorite button");
      button.dataset.loading = "false";
      return;
    }

    const isActive = button.classList.contains("is-favorite");

    // ⚡ Optimistic UI (instantané)
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

      // 🔁 Sync avec backend (sécurité)
      this.toggleUI(button, data.added);
    } catch (error) {
      console.error("Favorite toggle error:", error);

      // 🔙 Rollback UI
      this.toggleUI(button, isActive);
    }

    button.dataset.loading = "false";
  }

  toggleUI(button, isFavorite) {
    button.classList.toggle("is-favorite", isFavorite);

    // Si icône (SVG / font)
    const icon = button.querySelector("[data-icon]");
    if (icon) {
      icon.classList.toggle("active", isFavorite);
    }

    // Accessibilité
    button.setAttribute("aria-pressed", isFavorite ? "true" : "false");
  }
}
