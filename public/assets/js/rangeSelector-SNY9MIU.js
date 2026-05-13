export default function initDoubleSlider(slider) {
  if (!slider) return;

  const FILTER = slider.dataset.filter;

  const MIN = Number(slider.dataset.min);
  const MAX = Number(slider.dataset.max);
  const STEP = Number(slider.dataset.step) || 1;

  if (!Number.isFinite(MIN) || !Number.isFinite(MAX) || MIN >= MAX) {
    console.error("[DoubleSlider] dataset invalide", slider.dataset);
    return;
  }

  let currentMin = MIN;
  let currentMax = MAX;
  let activeThumb = null;

  const thumbs = {};

  /**
   * Conversion valeur → position pixel
   */
  const valueToPos = value =>
    ((value - MIN) / (MAX - MIN)) * slider.clientWidth;

  /**
   * Conversion position pixel → valeur
   */
  const posToValue = pos => {
    let val = MIN + (pos / slider.clientWidth) * (MAX - MIN);
    val = Math.round(val / STEP) * STEP;
    return Math.min(Math.max(val, MIN), MAX);
  };

  /**
   * Début drag souris
   */
  function startDrag(e, thumb) {
    e.preventDefault();
    activeThumb = thumb;

    document.addEventListener("mousemove", onDrag);
    document.addEventListener("mouseup", stopDrag);
  }

  /**
   * Drag souris
   */
  function onDrag(e) {
    if (!activeThumb) return;

    const pos = e.clientX - slider.getBoundingClientRect().left;
    const val = posToValue(pos);

    if (activeThumb === thumbs.thumbMin) {
      currentMin = Math.min(val, currentMax - STEP);
    } else {
      currentMax = Math.max(val, currentMin + STEP);
    }

    updateUI();
  }

  /**
   * Fin drag souris
   */
  function stopDrag() {
    activeThumb = null;

    document.removeEventListener("mousemove", onDrag);
    document.removeEventListener("mouseup", stopDrag);
  }

  /**
   * Début touch
   */
  function startTouch(e, thumb) {
    activeThumb = thumb;

    document.addEventListener("touchmove", onTouch, { passive: false });
    document.addEventListener("touchend", stopTouch);
  }

  /**
   * Touch move
   */
  function onTouch(e) {
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
  }

  /**
   * Fin touch
   */
  function stopTouch() {
    activeThumb = null;

    document.removeEventListener("touchmove", onTouch);
    document.removeEventListener("touchend", stopTouch);
  }

  /**
   * Construction du slider DOM
   */
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

  /**
   * Mise à jour UI slider + inputs + labels
   */
  const updateUI = () => {
    const left = valueToPos(currentMin);
    const right = valueToPos(currentMax);

    thumbs.thumbMin.style.left = left + "px";
    thumbs.thumbMax.style.left = right + "px";

    thumbs.rangeBar.style.left = left + "px";
    thumbs.rangeBar.style.width = right - left + "px";

    if (thumbs.valueMin) {
      thumbs.valueMin.textContent = currentMin.toLocaleString("fr-FR");
    }

    if (thumbs.valueMax) {
      thumbs.valueMax.textContent = currentMax.toLocaleString("fr-FR");
    }

    const root = slider.closest("form") || document;

    const targetMin = root.querySelector(slider.dataset.targetMin);
    const targetMax = root.querySelector(slider.dataset.targetMax);

    if (targetMin) {
      targetMin.textContent = currentMin.toLocaleString("fr-FR");
    }

    if (targetMax) {
      targetMax.textContent = currentMax.toLocaleString("fr-FR");
    }

    const inputMin = root.querySelector(slider.dataset.inputMin);
    const inputMax = root.querySelector(slider.dataset.inputMax);

    if (inputMin) inputMin.value = currentMin;
    if (inputMax) inputMax.value = currentMax;

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

  buildSlider();
  updateUI();

  window.addEventListener("resize", updateUI);
}
