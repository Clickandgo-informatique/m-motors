// rangeSelector.js
export default function initDoubleSlider(slider) {
  if (!slider) return;

  const FILTER = slider.dataset.filter;
  const MIN = Number.parseInt(slider.dataset.min) || 0;
  const MAX = Number.parseInt(slider.dataset.max) || 100;
  const STEP = Number.parseInt(slider.dataset.step) || 1;

  // Valeurs initiales
  let currentMin = Number(slider.dataset.valueLow) || MIN;
  let currentMax = Number(slider.dataset.valueHigh) || MAX;
  let activeThumb = null;

  // Création HTML interne si pas déjà présent
  if (!slider.querySelector(".slider-track")) {
    slider.innerHTML = `
      <div class="slider-track"></div>
      <div class="slider-range"></div>
      <div class="thumb thumb-min"><span class="thumb-value"></span></div>
      <div class="thumb thumb-max"><span class="thumb-value"></span></div>
    `;
  }

  const thumbMin = slider.querySelector(".thumb-min");
  const thumbMax = slider.querySelector(".thumb-max");
  const rangeBar = slider.querySelector(".slider-range");
  const valueMin = thumbMin.querySelector(".thumb-value");
  const valueMax = thumbMax.querySelector(".thumb-value");

  // Conversion valeur ↔ position
  const valueToPos = val => ((val - MIN) / (MAX - MIN)) * slider.clientWidth;
  const posToValue = pos => {
    let val = MIN + (pos / slider.clientWidth) * (MAX - MIN);
    val = Math.round(val / STEP) * STEP;
    return Math.min(Math.max(val, MIN), MAX);
  };

  // Mise à jour visuelle + inputs + dispatch event
  const updateUI = () => {
    currentMin = Math.min(currentMin, currentMax);
    currentMax = Math.max(currentMax, currentMin);

    const left = valueToPos(currentMin);
    const right = valueToPos(currentMax);

    thumbMin.style.left = left + "px";
    thumbMax.style.left = right + "px";
    rangeBar.style.left = left + "px";
    rangeBar.style.width = right - left + "px";

    valueMin.textContent = currentMin.toLocaleString("fr-FR");
    valueMax.textContent = currentMax.toLocaleString("fr-FR");

    // Inputs cachés
    if (slider.dataset.inputMin) {
      const inputMin = document.querySelector(
        `input[name="${slider.dataset.inputMin}"]`
      );
      if (inputMin) inputMin.value = currentMin;
    }
    if (slider.dataset.inputMax) {
      const inputMax = document.querySelector(
        `input[name="${slider.dataset.inputMax}"]`
      );
      if (inputMax) inputMax.value = currentMax;
    }

    // Dispatch event custom
    slider.dispatchEvent(
      new CustomEvent("sliderChanged", {
        bubbles: true,
        detail: { filter: FILTER, min: currentMin, max: currentMax }
      })
    );
  };

  // Drag souris
  const startDrag = (e, thumb) => {
    e.preventDefault();
    activeThumb = thumb;
    document.addEventListener("mousemove", onDrag);
    document.addEventListener("mouseup", stopDrag);
  };

  const onDrag = e => {
    if (!activeThumb) return;
    const rect = slider.getBoundingClientRect();
    const pos = e.clientX - rect.left;
    const val = posToValue(pos);

    if (activeThumb === thumbMin) currentMin = Math.min(val, currentMax - STEP);
    else currentMax = Math.max(val, currentMin + STEP);

    updateUI();
  };

  const stopDrag = () => {
    activeThumb = null;
    document.removeEventListener("mousemove", onDrag);
    document.removeEventListener("mouseup", stopDrag);
  };

  // Drag touch
  const startTouch = (e, thumb) => {
    activeThumb = thumb;
    document.addEventListener("touchmove", onTouch, { passive: false });
    document.addEventListener("touchend", stopTouch);
  };

  const onTouch = e => {
    e.preventDefault();
    if (!activeThumb) return;
    const rect = slider.getBoundingClientRect();
    const pos = e.touches[0].clientX - rect.left;
    const val = posToValue(pos);

    if (activeThumb === thumbMin) currentMin = Math.min(val, currentMax - STEP);
    else currentMax = Math.max(val, currentMin + STEP);

    updateUI();
  };

  const stopTouch = () => {
    activeThumb = null;
    document.removeEventListener("touchmove", onTouch);
    document.removeEventListener("touchend", stopTouch);
  };

  // Événements
  thumbMin.addEventListener("mousedown", e => startDrag(e, thumbMin));
  thumbMax.addEventListener("mousedown", e => startDrag(e, thumbMax));
  thumbMin.addEventListener("touchstart", e => startTouch(e, thumbMin));
  thumbMax.addEventListener("touchstart", e => startTouch(e, thumbMax));

  // Initialisation
  updateUI();

  // Redimensionnement
  window.addEventListener("resize", updateUI);

  /**
   * Expose une méthode resetSlider() pour remettre le slider à ses valeurs initiales
   * (utilisée par VehiclesFilter lors du clic sur un badge remove)
   */
  slider.resetSlider = () => {
    currentMin = MIN;
    currentMax = MAX;
    updateUI();
  };
}
