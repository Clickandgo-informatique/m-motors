export default function initDoubleSlider(slider) {
  console.log("DoubleSlider initialisé");

  if (!slider) return;

  const FILTER = slider.dataset.filter;

  const MIN = Number(slider.dataset.min);
  const MAX = Number(slider.dataset.max);
  const STEP = Number(slider.dataset.step) || 1;

  if (!Number.isFinite(MIN) || !Number.isFinite(MAX) || MIN >= MAX) {
    console.error("[DoubleSlider] dataset invalide", slider.dataset);
    return;
  }

  /*
   * IMPORTANT :
   * Synchronisation avec inputs hidden existants
   */
  const root = slider.closest("form") || document;

  const inputMinEl = root.querySelector(`input[name="${slider.dataset.inputMin}"]`);
  const inputMaxEl = root.querySelector(`input[name="${slider.dataset.inputMax}"]`);

  let currentMin = inputMinEl?.value ? Number(inputMinEl.value) : MIN;
  let currentMax = inputMaxEl?.value ? Number(inputMaxEl.value) : MAX;

  let activeThumb = null;

  const thumbs = {};

  /*
   * Debounce AJAX sécurisé
   */
  const scheduleFetch = () => {
    clearTimeout(slider._fetchTimeout);

    slider._fetchTimeout = setTimeout(() => {
      const form = slider.closest("form");
      if (!form) return;

      if (form.dataset.loading === "1") return;

      form.dispatchEvent(new Event("change", { bubbles: true }));
    }, 300);
  };

  const valueToPos = value => ((value - MIN) / (MAX - MIN)) * slider.clientWidth;

  const posToValue = pos => {
    let val = MIN + (pos / slider.clientWidth) * (MAX - MIN);
    val = Math.round(val / STEP) * STEP;
    return Math.min(Math.max(val, MIN), MAX);
  };

  function startDrag(e, thumb) {
    e.preventDefault();
    activeThumb = thumb;

    document.addEventListener("mousemove", onDrag);
    document.addEventListener("mouseup", stopDrag);
  }

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

  function stopDrag() {
    activeThumb = null;

    document.removeEventListener("mousemove", onDrag);
    document.removeEventListener("mouseup", stopDrag);
  }

  function startTouch(e, thumb) {
    activeThumb = thumb;

    document.addEventListener("touchmove", onTouch, { passive: false });
    document.addEventListener("touchend", stopTouch);
  }

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

  function stopTouch() {
    activeThumb = null;

    document.removeEventListener("touchmove", onTouch);
    document.removeEventListener("touchend", stopTouch);
  }

  /*
   * Construction DOM propre (reset safe)
   */
  const buildSlider = () => {
    slider.replaceChildren();

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

    thumbs.thumbMin.addEventListener("mousedown", e => startDrag(e, thumbs.thumbMin));
    thumbs.thumbMax.addEventListener("mousedown", e => startDrag(e, thumbs.thumbMax));

    thumbs.thumbMin.addEventListener("touchstart", e => startTouch(e, thumbs.thumbMin));
    thumbs.thumbMax.addEventListener("touchstart", e => startTouch(e, thumbs.thumbMax));
  };

  /*
   * UI update + sync inputs hidden
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

    const targetMin = root.querySelector(slider.dataset.targetMin);
    const targetMax = root.querySelector(slider.dataset.targetMax);

    if (targetMin) {
      targetMin.textContent = currentMin.toLocaleString("fr-FR");
    }

    if (targetMax) {
      targetMax.textContent = currentMax.toLocaleString("fr-FR");
    }

    /*
     * CRITIQUE : sync inputs hidden
     */
    if (inputMinEl) inputMinEl.value = currentMin;
    if (inputMaxEl) inputMaxEl.value = currentMax;

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

    scheduleFetch();
  };

  buildSlider();
  updateUI();

  /*
   * Resize listener sécurisé (évite accumulation)
   */
  if (!slider._resizeBound) {
    window.addEventListener("resize", updateUI);
    slider._resizeBound = true;
  }
}
