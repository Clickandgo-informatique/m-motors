// rangeSelector.js
/**
 * Initialisation d'un double slider (min/max) pour les filtres.
 * Support souris et touch.
 * Ajoute une méthode `resetSlider()` sur l'élément DOM pour réinitialiser le slider.
 */
export default function initDoubleSlider(slider) {
  if (!slider) return;

  const FILTER = slider.dataset.filter; // ex: "mileage" ou "year"
  const MIN = Number(slider.dataset.min) || 0;
  const MAX = Number(slider.dataset.max) || 100;
  const STEP = Number(slider.dataset.step) || 1;

  // Création de la structure HTML interne si elle n'existe pas déjà
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

  let currentMin = MIN;
  let currentMax = MAX;
  let activeThumb = null;

  // Conversion valeur -> position
  const valueToPos = value => ((value - MIN) / (MAX - MIN)) * slider.clientWidth;

  // Conversion position -> valeur
  const posToValue = pos => {
    let val = MIN + (pos / slider.clientWidth) * (MAX - MIN);
    val = Math.round(val / STEP) * STEP;
    return Math.min(Math.max(val, MIN), MAX);
  };

  // Mise à jour visuelle + inputs + dispatch event
  const updateUI = () => {
    const left = valueToPos(currentMin);
    const right = valueToPos(currentMax);

    thumbMin.style.left = left + "px";
    thumbMax.style.left = right + "px";
    rangeBar.style.left = left + "px";
    rangeBar.style.width = right - left + "px";

    valueMin.textContent = currentMin.toLocaleString("fr-FR");
    valueMax.textContent = currentMax.toLocaleString("fr-FR");

    // Inputs liés via dataset
    const inputMin = slider.dataset.inputMin ? document.querySelector(`[name="${slider.dataset.inputMin}"]`) : null;
    const inputMax = slider.dataset.inputMax ? document.querySelector(`[name="${slider.dataset.inputMax}"]`) : null;
    if (inputMin) inputMin.value = currentMin;
    if (inputMax) inputMax.value = currentMax;

    // Dispatch événement custom pour VehiclesFilter
    slider.dispatchEvent(new CustomEvent("sliderChanged", {
      bubbles: true,
      detail: { filter: FILTER, min: currentMin, max: currentMax }
    }));
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

  // Touch
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

  // Méthode pour reset le slider
  slider.resetSlider = () => {
    currentMin = MIN;
    currentMax = MAX;
    updateUI();
  };

  // Initialisation
  updateUI();

  // Ajuste le slider si la fenêtre change de taille
  window.addEventListener("resize", updateUI);
}