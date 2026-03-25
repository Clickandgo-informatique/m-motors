// Gestion du clic sur badges
if (this.summaryContainer) {
  this.summaryContainer.addEventListener("click", e => {
    if (!e.target.matches(".badge-remove")) return;

    const filter = e.target.dataset.filter;
    const value = e.target.dataset.value;

    // Si badge lié à un slider, reset visuel + inputs
    const slider = this.form.querySelector(
      `.double-slider[data-filter="${filter}"]`
    );
    if (slider && typeof slider.resetSlider === "function") {
      slider.resetSlider();
    } else {
      // Checkbox classiques
      const checkboxes = this.form.querySelectorAll(
        `input[name="filters[${filter}][]"]`
      );
      checkboxes.forEach(cb => {
        if (cb.value === value) cb.checked = false;
      });
    }

    // Mise à jour des badges et filtrage AJAX
    if (this.badges) this.badges.updateBadges();
    this.submitFilters();
  });
}
