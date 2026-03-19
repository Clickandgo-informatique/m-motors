document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".double-slider").forEach(initDoubleSlider);
});

export function initDoubleSlider(slider) {
  // Lecture des datasets
  const FILTER = slider.dataset.filter; // ex: "mileage"
  const MIN = Number.parseInt(slider.dataset.min);
  const MAX = Number.parseInt(slider.dataset.max);
  const STEP = Number.parseInt(slider.dataset.step);

  // Création dynamique des éléments internes
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

  // Convertir valeur en position px
  function valueToPos(value) {
    const width = slider.clientWidth;
    return ((value - MIN) / (MAX - MIN)) * width;
  }

  // Convertir position px en valeur arrondie
  function posToValue(pos) {
    const width = slider.clientWidth;
    let val = MIN + (pos / width) * (MAX - MIN);
    val = Math.round(val / STEP) * STEP;
    return Math.min(Math.max(val, MIN), MAX);
  }

  // Mise à jour UI
  function updateUI() {
    const left = valueToPos(currentMin);
    const right = valueToPos(currentMax);

    thumbMin.style.left = left + "px";
    thumbMax.style.left = right + "px";

    rangeBar.style.left = left + "px";
    rangeBar.style.width = right - left + "px";

    valueMin.textContent = currentMin.toLocaleString("fr-FR");
    valueMax.textContent = currentMax.toLocaleString("fr-FR");

    // Événement générique envoyé au système de filtres
    document.dispatchEvent(
      new CustomEvent("sliderChanged", {
        detail: {
          filter: FILTER,
          min: currentMin,
          max: currentMax
        }
      })
    );
  }

  // Drag souris
  let activeThumb = null;

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

    if (activeThumb === thumbMin) {
      currentMin = Math.min(val, currentMax - STEP);
    } else {
      currentMax = Math.max(val, currentMin + STEP);
    }
    updateUI();
  }

  function stopDrag() {
    activeThumb = null;
    document.removeEventListener("mousemove", onDrag);
    document.removeEventListener("mouseup", stopDrag);
  }

  // Touch events
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

    if (activeThumb === thumbMin) {
      currentMin = Math.min(val, currentMax - STEP);
    } else {
      currentMax = Math.max(val, currentMin + STEP);
    }
    updateUI();
  }

  function stopTouch() {
    activeThumb = null;
    document.removeEventListener("touchmove", onTouch);
    document.removeEventListener("touchend", stopTouch);
  }

  // Bind events
  thumbMin.addEventListener("mousedown", e => startDrag(e, thumbMin));
  thumbMax.addEventListener("mousedown", e => startDrag(e, thumbMax));

  thumbMin.addEventListener("touchstart", e => startTouch(e, thumbMin));
  thumbMax.addEventListener("touchstart", e => startTouch(e, thumbMax));

  // Initialisation
  updateUI();
}
