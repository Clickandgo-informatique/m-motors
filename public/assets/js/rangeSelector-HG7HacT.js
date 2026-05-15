export default function initRangeSelector(slider) {
  if (!(slider instanceof HTMLElement)) return;

  const MIN = Number(slider.dataset.min);
  const MAX = Number(slider.dataset.max);
  const STEP = Number(slider.dataset.step) || 1;
  const FILTER = slider.dataset.filter;

  if (!Number.isFinite(MIN) || !Number.isFinite(MAX) || MIN >= MAX) {
    console.error("[RangeSelector] invalid dataset", slider.dataset);
    return;
  }

  const form = slider.closest("form") || document;

  const inputMin = form.querySelector(`input[name="${slider.dataset.inputMin}"]`);
  const inputMax = form.querySelector(`input[name="${slider.dataset.inputMax}"]`);

  let currentMin = inputMin?.value ? Number(inputMin.value) : MIN;
  let currentMax = inputMax?.value ? Number(inputMax.value) : MAX;

  let activeThumb = null;

  const clamp = val => Math.min(Math.max(val, MIN), MAX);

  const valueToPos = val => ((val - MIN) / (MAX - MIN)) * slider.clientWidth;

  const posToValue = pos => {
    let val = MIN + (pos / slider.clientWidth) * (MAX - MIN);
    val = Math.round(val / STEP) * STEP;
    return clamp(val);
  };

  const scheduleFetch = () => {
    clearTimeout(slider._t);

    slider._t = setTimeout(() => {
      if (form.dataset.loading === "1") return;
      form.dispatchEvent(new Event("change", { bubbles: true }));
    }, 250);
  };

  const build = () => {
    slider.innerHTML = `
      <div class="slider-track"></div>
      <div class="slider-range"></div>
      <div class="thumb thumb-min"></div>
      <div class="thumb thumb-max"></div>
    `;

    slider.track = slider.querySelector(".slider-range");
    slider.minThumb = slider.querySelector(".thumb-min");
    slider.maxThumb = slider.querySelector(".thumb-max");

    slider.minThumb.addEventListener("mousedown", e => startDrag(e, "min"));
    slider.maxThumb.addEventListener("mousedown", e => startDrag(e, "max"));
  };

  const update = () => {
    const left = valueToPos(currentMin);
    const right = valueToPos(currentMax);

    slider.minThumb.style.left = `${left}px`;
    slider.maxThumb.style.left = `${right}px`;

    slider.track.style.left = `${left}px`;
    slider.track.style.width = `${right - left}px`;

    if (inputMin) inputMin.value = currentMin;
    if (inputMax) inputMax.value = currentMax;

    const minDisplay = document.querySelector(slider.dataset.targetMin);
    const maxDisplay = document.querySelector(slider.dataset.targetMax);

    if (minDisplay) minDisplay.textContent = currentMin.toLocaleString("fr-FR");
    if (maxDisplay) maxDisplay.textContent = currentMax.toLocaleString("fr-FR");

    slider.dispatchEvent(
      new CustomEvent("sliderChanged", {
        bubbles: true,
        detail: { filter: FILTER, min: currentMin, max: currentMax }
      })
    );

    scheduleFetch();
  };

  const startDrag = (e, type) => {
    activeThumb = type;

    document.addEventListener("mousemove", onMove);
    document.addEventListener("mouseup", stop);
  };

  const onMove = e => {
    const rect = slider.getBoundingClientRect();
    const pos = e.clientX - rect.left;
    const val = posToValue(pos);

    if (activeThumb === "min") {
      currentMin = Math.min(val, currentMax - STEP);
    } else {
      currentMax = Math.max(val, currentMin + STEP);
    }

    update();
  };

  const stop = () => {
    activeThumb = null;
    document.removeEventListener("mousemove", onMove);
    document.removeEventListener("mouseup", stop);
  };

  build();
  update();

  window.addEventListener("resize", update);
}
