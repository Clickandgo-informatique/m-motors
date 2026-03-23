export default function initDoubleSlider(slider) {
  const FILTER = slider.dataset.filter;
  const MIN = Number.parseInt(slider.dataset.min);
  const MAX = Number.parseInt(slider.dataset.max);
  const STEP = Number.parseInt(slider.dataset.step);

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

  let currentMin = MIN,
    currentMax = MAX,
    activeThumb = null;

  function valueToPos(value) {
    return ((value - MIN) / (MAX - MIN)) * slider.clientWidth;
  }
  function posToValue(pos) {
    let val = MIN + (pos / slider.clientWidth) * (MAX - MIN);
    return Math.min(MAX, Math.max(MIN, Math.round(val / STEP) * STEP));
  }

  function updateUI() {
    const left = valueToPos(currentMin);
    const right = valueToPos(currentMax);
    thumbMin.style.left = left + "px";
    thumbMax.style.left = right + "px";
    rangeBar.style.left = left + "px";
    rangeBar.style.width = right - left + "px";
    valueMin.textContent = currentMin.toLocaleString("fr-FR");
    valueMax.textContent = currentMax.toLocaleString("fr-FR");
    const externalMin = document.getElementById(`${FILTER}-min-value`);
    const externalMax = document.getElementById(`${FILTER}-max-value`);
    if (externalMin)
      externalMin.textContent = currentMin.toLocaleString("fr-FR");
    if (externalMax)
      externalMax.textContent = currentMax.toLocaleString("fr-FR");
  }

  function emitChange() {
    document.dispatchEvent(
      new CustomEvent("sliderChanged", {
        detail: { filter: FILTER, min: currentMin, max: currentMax }
      })
    );
  }

  function startDrag(e, thumb) {
    e.preventDefault();
    activeThumb = thumb;
    document.addEventListener("mousemove", onDrag);
    document.addEventListener("mouseup", stopDrag);
  }
  function onDrag(e) {
    if (!activeThumb) return;
    const rect = slider.getBoundingClientRect();
    let val = posToValue(e.clientX - rect.left);
    if (activeThumb === thumbMin) currentMin = Math.min(val, currentMax - STEP);
    else currentMax = Math.max(val, currentMin + STEP);
    updateUI();
    emitChange();
  }
  function stopDrag() {
    activeThumb = null;
    document.removeEventListener("mousemove", onDrag);
    document.removeEventListener("mouseup", stopDrag);
  }

  thumbMin.addEventListener("mousedown", e => startDrag(e, thumbMin));
  thumbMax.addEventListener("mousedown", e => startDrag(e, thumbMax));

  updateUI(); // UI initial mais pas d'emit
}
