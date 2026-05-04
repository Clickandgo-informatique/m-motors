export default function initDoubleSlider(slider) {
  if (!slider) return;

  const FILTER = slider.dataset.filter;

  /**
   * IMPORTANT :
   * ton HTML fournit parfois valueLow/valueHigh mais min/max peuvent être vides
   */
  const rawMin = slider.dataset.min || slider.dataset.valueLow;
  const rawMax = slider.dataset.max || slider.dataset.valueHigh;

  const MIN = parseInt(rawMin, 10);
  const MAX = parseInt(rawMax, 10);

  const STEP = Number(slider.dataset.step) || 1;

  if (Number.isNaN(MIN) || Number.isNaN(MAX)) {
    console.error("[DoubleSlider] min/max invalides", slider.dataset);
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

    thumbs.valueMin.textContent = currentMin.toLocaleString("fr-FR");
    thumbs.valueMax.textContent = currentMax.toLocaleString("fr-FR");

    if (inputMin) inputMin.value = currentMin;
    if (inputMax) inputMax.value = currentMax;

    if (targetMinLabel) {
      targetMinLabel.textContent = currentMin.toLocaleString("fr-FR");
    }

    if (targetMaxLabel) {
      targetMaxLabel.textContent = currentMax.toLocaleString("fr-FR");
    }

    slider.dispatchEvent(
      new CustomEvent("sliderChanged", {
        bubbles: true,
        detail: {
          filter: FILTER,
          min: currentMin,
          max: currentMax
        }
      })
    );
  };

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

    if (activeThumb === thumbs.thumbMin) {
      currentMin = Math.min(val, currentMax - STEP);
    } else {
      currentMax = Math.max(val, currentMin + STEP);
    }

    updateUI();
  };

  const stopDrag = () => {
    activeThumb = null;
    document.removeEventListener("mousemove", onDrag);
    document.removeEventListener("mouseup", stopDrag);
  };

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

    if (activeThumb === thumbs.thumbMin) {
      currentMin = Math.min(val, currentMax - STEP);
    } else {
      currentMax = Math.max(val, currentMin + STEP);
    }

    updateUI();
  };

  const stopTouch = () => {
    activeThumb = null;
    document.removeEventListener("touchmove", onTouch);
    document.removeEventListener("touchend", stopTouch);
  };

  slider.resetSlider = () => {
    currentMin = MIN;
    currentMax = MAX;
    updateUI();
  };

  updateUI();

  window.addEventListener("resize", updateUI);
}
