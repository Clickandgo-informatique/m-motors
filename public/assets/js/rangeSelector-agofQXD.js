// rangeSelector.js
/**
 * Initialisation d'un double slider (min/max) avec labels et inputs liés.
 * Support souris et tactile.
 */
export default function initDoubleSlider(slider) {
  if (!slider) return;

  const FILTER = slider.dataset.filter;
  const MIN = Number(slider.dataset.min) || 0;
  const MAX = Number(slider.dataset.max) || 100;
  const STEP = Number(slider.dataset.step) || 1;

  // Inputs et labels ciblés depuis les data attributes
  const inputMin = document.querySelector(slider.dataset.inputMin);
  const inputMax = document.querySelector(slider.dataset.inputMax);
  const targetMinLabel = document.querySelector(slider.dataset.targetMin);
  const targetMaxLabel = document.querySelector(slider.dataset.targetMax);

  // Valeurs actuelles
  let currentMin = MIN;
  let currentMax = MAX;
  let activeThumb = null;

  // Reconstruire le slider visuel
  const buildSlider = () => {
    slider.innerHTML = `
      <div class="slider-track"></div>
      <div class="slider-range"></div>
      <div class="thumb thumb-min"><span class="thumb-value"></span></div>
      <div class="thumb thumb-max"><span class="thumb-value"></span></div>
    `;

    thumbs.thumbMin = slider.querySelector(".thumb-min");
    thumbs.thumbMax = slider.querySelector(".thumb-max");
    thumbs.valueMin = thumbs.thumbMin.querySelector(".thumb-value");
    thumbs.valueMax = thumbs.thumbMax.querySelector(".thumb-value");
    thumbs.rangeBar = slider.querySelector(".slider-range");
  };

  const thumbs = {};
  buildSlider();

  // Conversion valeur <-> position
  const valueToPos = value =>
    ((value - MIN) / (MAX - MIN)) * slider.clientWidth;
  const posToValue = pos => {
    let val = MIN + (pos / slider.clientWidth) * (MAX - MIN);
    val = Math.round(val / STEP) * STEP;
    return Math.min(Math.max(val, MIN), MAX);
  };

  // Mise à jour visuelle et inputs
  const updateUI = () => {
    currentMin = Math.min(currentMin, currentMax);
    currentMax = Math.max(currentMax, currentMin);

    const left = valueToPos(currentMin);
    const right = valueToPos(currentMax);

    thumbs.thumbMin.style.left = left + "px";
    thumbs.thumbMax.style.left = right + "px";
    thumbs.rangeBar.style.left = left + "px";
    thumbs.rangeBar.style.width = right - left + "px";

    thumbs.valueMin.textContent = currentMin.toLocaleString("fr-FR");
    thumbs.valueMax.textContent = currentMax.toLocaleString("fr-FR");

    if (inputMin) inputMin.value = currentMin;
    if (inputMax) inputMax.value = currentMax;
    if (targetMinLabel)
      targetMinLabel.textContent = currentMin.toLocaleString("fr-FR");
    if (targetMaxLabel)
      targetMaxLabel.textContent = currentMax.toLocaleString("fr-FR");

    // Dispatch custom event
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
    const pos = e.clientX - slider.getBoundingClientRect().left;
    const val = posToValue(pos);
    if (activeThumb === thumbs.thumbMin)
      currentMin = Math.min(val, currentMax - STEP);
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
    const pos = e.touches[0].clientX - slider.getBoundingClientRect().left;
    const val = posToValue(pos);
    if (activeThumb === thumbs.thumbMin)
      currentMin = Math.min(val, currentMax - STEP);
    else currentMax = Math.max(val, currentMin + STEP);
    updateUI();
  };
  const stopTouch = () => {
    activeThumb = null;
    document.removeEventListener("touchmove", onTouch);
    document.removeEventListener("touchend", stopTouch);
  };

  // Événements
  thumbs.thumbMin.addEventListener("mousedown", e =>
    startDrag(e, thumbs.thumbMin)
  );
  thumbs.thumbMax.addEventListener("mousedown", e =>
    startDrag(e, thumbs.thumbMax)
  );
  thumbs.thumbMin.addEventListener("touchstart", e =>
    startTouch(e, thumbs.thumbMin)
  );
  thumbs.thumbMax.addEventListener("touchstart", e =>
    startTouch(e, thumbs.thumbMax)
  );

  // Reset complet du slider
  slider.resetSlider = () => {
    currentMin = MIN;
    currentMax = MAX;
    buildSlider(); // Reconstruit le slider et thumbs
    updateUI();
  };

  // Initialisation
  updateUI();

  // Resize : recalcul des positions
  window.addEventListener("resize", updateUI);
}
