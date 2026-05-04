export default function initDoubleSlider(slider) {
  if (!slider) return;

  const FILTER = slider.dataset.filter;

  // Lecture stricte
  const MIN = Number(slider.dataset.min);
  const MAX = Number(slider.dataset.max);
  const STEP = Number(slider.dataset.step) || 1;

  // Sécurité forte
  if (!Number.isFinite(MIN) || !Number.isFinite(MAX) || MIN >= MAX) {
    console.error("[DoubleSlider] dataset invalide", slider.dataset);
    return;
  }

  const inputMin = document.querySelector(slider.dataset.inputMin);
  const inputMax = document.querySelector(slider.dataset.inputMax);
  const targetMinLabel = document.querySelector(slider.dataset.targetMin);
  const targetMaxLabel = document.querySelector(slider.dataset.targetMax);

  let currentMin = MIN;
  let currentMax = MAX;
  let activeThumb = null;

  const thumbs = {};

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
  };

  buildSlider();

  const valueToPos = value =>
    ((value - MIN) / (MAX - MIN)) * slider.clientWidth;

  const posToValue = pos => {
    let val = MIN + (pos / slider.clientWidth) * (MAX - MIN);
    val = Math.round(val / STEP) * STEP;
    return Math.min(Math.max(val, MIN), MAX);
  };

  const updateUI = () => {
    currentMin = Math.min(currentMin, currentMax);
    currentMax = Math.max(currentMax, currentMin);

    const left = valueToPos(currentMin);
    const right = valueToPos(currentMax);

    thumbs.thumbMin.style.left = left + "px";
    thumbs.thumbMax.style.left = right + "px";
    thumbs.rangeBar.style.left = left + "px";
    thumbs.rangeBar.style.width = right - left + "px";

    thumbs.valueMin.textContent = currentMin;
    thumbs.valueMax.textContent = currentMax;

    if (inputMin) inputMin.value = currentMin;
    if (inputMax) inputMax.value = currentMax;

    if (targetMinLabel) targetMinLabel.textContent = currentMin;
    if (targetMaxLabel) targetMaxLabel.textContent = currentMax;

    slider.dispatchEvent(
      new CustomEvent("sliderChanged", {
        bubbles: true,
        detail: { filter: FILTER, min: currentMin, max: currentMax }
      })
    );
  };

  updateUI();

  window.addEventListener("resize", updateUI);
}
