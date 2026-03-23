// rangeSelector.js
export default function initDoubleSlider(slider) {
  const FILTER = slider.dataset.filter; // ex: "mileage"
  const MIN = Number.parseInt(slider.dataset.min);
  const MAX = Number.parseInt(slider.dataset.max);
  const STEP = Number.parseInt(slider.dataset.step);

  // Création de la structure HTML interne du slider
  slider.innerHTML = `
    <div class="slider-track"></div>
    <div class="slider-range"></div>
    <div class="thumb thumb-min"><span class="thumb-value"></span></div>
    <div class="thumb thumb-max"><span class="thumb-value"></span></div>
  `;

  const thumbMin = slider.querySelector(".thumb-min");
  const thumbMax = slider.querySelector(".thumb-max");
  const rangeBar = slider.querySelector(".slider-range");
  const valueMin = thumbMin.querySelector(".thumb-value");
  const valueMax = thumbMax.querySelector(".thumb-value");

  let currentMin = MIN;
  let currentMax = MAX;
  let activeThumb = null;

  // Conversion valeur -> position
  function valueToPos(value) {
    const width = slider.clientWidth;
    return ((value - MIN) / (MAX - MIN)) * width;
  }

  // Conversion position -> valeur
  function posToValue(pos) {
    const width = slider.clientWidth;
    let val = MIN + (pos / width) * (MAX - MIN);
    val = Math.round(val / STEP) * STEP;
    return Math.min(Math.max(val, MIN), MAX);
  }

  // Mise à jour visuelle du slider et dispatch de l'événement
  function updateUI() {
    const left = valueToPos(currentMin);
    const right = valueToPos(currentMax);

    thumbMin.style.left = left + "px";
    thumbMax.style.left = right + "px";
    rangeBar.style.left = left + "px";
    rangeBar.style.width = right - left + "px";

    valueMin.textContent = currentMin.toLocaleString("fr-FR");
    valueMax.textContent = currentMax.toLocaleString("fr-FR");

    // Mise à jour des inputs cachés du formulaire
    const externalMin = document.querySelector(
      `input[name="filters[${FILTER}Min]"]`
    );
    const externalMax = document.querySelector(
      `input[name="filters[${FILTER}Max]"]`
    );
    if (externalMin) externalMin.value = currentMin;
    if (externalMax) externalMax.value = currentMax;

    // Dispatch événement custom pour le JS du formulaire
    slider.dispatchEvent(
      new CustomEvent("sliderChanged", {
        bubbles: true,
        detail: { filter: FILTER, min: currentMin, max: currentMax }
      })
    );
  }

  // Drag avec souris
  function startDrag(e, thumb) {
    e.preventDefault();
    activeThumb = thumb;
    document.addEventListener("mousemove", onDrag);
    document.addEventListener("mouseup", stopDrag);
  }

  function onDrag(e) {
    if (!activeThumb) return;
    const rect = slider.getBoundingClientRect();
    let pos = e.clientX - rect.left;
    let val = posToValue(pos);

    if (activeThumb === thumbMin) currentMin = Math.min(val, currentMax - STEP);
    else currentMax = Math.max(val, currentMin + STEP);

    updateUI();
  }

  function stopDrag() {
    activeThumb = null;
    document.removeEventListener("mousemove", onDrag);
    document.removeEventListener("mouseup", stopDrag);
  }

  // Touch support
  function startTouch(e, thumb) {
    activeThumb = thumb;
    document.addEventListener("touchmove", onTouch);
    document.addEventListener("touchend", stopTouch);
  }

  function onTouch(e) {
    if (!activeThumb) return;
    const rect = slider.getBoundingClientRect();
    let pos = e.touches[0].clientX - rect.left;
    let val = posToValue(pos);

    if (activeThumb === thumbMin) currentMin = Math.min(val, currentMax - STEP);
    else currentMax = Math.max(val, currentMin + STEP);

    updateUI();
  }

  function stopTouch() {
    activeThumb = null;
    document.removeEventListener("touchmove", onTouch);
    document.removeEventListener("touchend", stopTouch);
  }

  // Événements souris
  thumbMin.addEventListener("mousedown", e => startDrag(e, thumbMin));
  thumbMax.addEventListener("mousedown", e => startDrag(e, thumbMax));

  // Événements touch
  thumbMin.addEventListener("touchstart", e => startTouch(e, thumbMin));
  thumbMax.addEventListener("touchstart", e => startTouch(e, thumbMax));

  // Initialisation de l'affichage
  updateUI();
}
