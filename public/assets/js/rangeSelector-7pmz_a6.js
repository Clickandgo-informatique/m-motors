const MIN = 0;
const MAX = 300000;
const STEP = 10000;

const thumbMin = document.getElementById("thumb-min");
const thumbMax = document.getElementById("thumb-max");
const rangeBar = document.querySelector(".slider-range");

const displayMin = document.getElementById("mileage-min-value");
const displayMax = document.getElementById("mileage-max-value");
const slider = document.querySelector(".double-slider");

let currentMin = MIN;
let currentMax = MAX;

// Convertir valeur en position px
function valueToPos(value) {
  const width = slider.clientWidth;
  return ((value - MIN) / (MAX - MIN)) * width;
}

// Convertir position px en valeur
function posToValue(pos) {
  const width = slider.clientWidth;
  let val = MIN + (pos / width) * (MAX - MIN);
  // arrondi au step
  val = Math.round(val / STEP) * STEP;
  return Math.min(Math.max(val, MIN), MAX);
}

// Mettre à jour la position des thumbs et de la barre
function updateUI() {
  const left = valueToPos(currentMin);
  const right = valueToPos(currentMax);

  thumbMin.style.left = left + "px";
  thumbMax.style.left = right + "px";

  rangeBar.style.left = left + "px";
  rangeBar.style.width = right - left + "px";

  displayMin.textContent = currentMin.toLocaleString("fr-FR");
  displayMax.textContent = currentMax.toLocaleString("fr-FR");

  triggerFilter();
}

// Dragging
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

// Touch support
function startTouch(e, thumb) {
  activeThumb = thumb;
  document.addEventListener("touchmove", onTouch);
  document.addEventListener("touchend", stopTouch);
}

function onTouch(e) {
  if (!activeThumb) return;
  const rect = slider.getBoundingClientRect();
  let touch = e.touches[0];
  let pos = touch.clientX - rect.left;
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

// Events
thumbMin.addEventListener("mousedown", e => startDrag(e, thumbMin));
thumbMax.addEventListener("mousedown", e => startDrag(e, thumbMax));

thumbMin.addEventListener("touchstart", e => startTouch(e, thumbMin));
thumbMax.addEventListener("touchstart", e => startTouch(e, thumbMax));

// Initialisation
updateUI();

// Hook AJAX pour Symfony
function triggerFilter() {
  // récupérer currentMin et currentMax
  console.log({ mileageMin: currentMin, mileageMax: currentMax });

  // fetch('/ajax/filter', { method:'POST', body: FormData }) etc.
}
