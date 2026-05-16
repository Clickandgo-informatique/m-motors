export default function initDoubleSlider(slider) {
  if (!slider) return;

  const MIN = Number(slider.dataset.min);
  const MAX = Number(slider.dataset.max);
  const STEP = Number(slider.dataset.step) || 1;

  if (!Number.isFinite(MIN) || !Number.isFinite(MAX) || MIN >= MAX) {
    console.error("[RangeSelector] invalid dataset", slider.dataset);
    return;
  }

  const form = slider.closest("form") || document;

  const inputMinEl = slider.querySelector(`input[name="${slider.dataset.inputMin}"]`);
  const inputMaxEl = slider.querySelector(`input[name="${slider.dataset.inputMax}"]`);

  let currentMin = inputMinEl?.value ? Number(inputMinEl.value) : MIN;
  let currentMax = inputMaxEl?.value ? Number(inputMaxEl.value) : MAX;

  let active = null;
  const thumbs = {};

  function sanitize(min, max) {
    if (!Number.isFinite(min)) min = MIN;
    if (!Number.isFinite(max)) max = MAX;

    if (min < MIN) min = MIN;
    if (max > MAX) max = MAX;

    if (min > max) [min, max] = [max, min];

    return [min, max];
  }

  function valueToPos(v) {
    return ((v - MIN) / (MAX - MIN)) * slider.clientWidth;
  }

  function posToValue(p) {
    let v = MIN + (p / slider.clientWidth) * (MAX - MIN);
    v = Math.round(v / STEP) * STEP;
    return Math.min(Math.max(v, MIN), MAX);
  }

  function scheduleFetch() {
    clearTimeout(slider._t);

    slider._t = setTimeout(() => {
      if (!form || form.dataset.loading === "1") return;
      form.dispatchEvent(new Event("change", { bubbles: true }));
    }, 250);
  }

  function build() {
    slider.innerHTML = `
      <div class="slider-track"></div>
      <div class="slider-range"></div>
      <div class="thumb thumb-min"><span></span></div>
      <div class="thumb thumb-max"><span></span></div>
    `;

    thumbs.min = slider.querySelector(".thumb-min");
    thumbs.max = slider.querySelector(".thumb-max");
    thumbs.range = slider.querySelector(".slider-range");
  }

  function update() {
    if (slider.clientWidth === 0) {
      requestAnimationFrame(update);
      return;
    }

    [currentMin, currentMax] = sanitize(currentMin, currentMax);

    const left = valueToPos(currentMin);
    const right = valueToPos(currentMax);

    thumbs.min.style.left = left + "px";
    thumbs.max.style.left = right + "px";

    thumbs.range.style.left = left + "px";
    thumbs.range.style.width = (right - left) + "px";

    if (inputMinEl) inputMinEl.value = currentMin;
    if (inputMaxEl) inputMaxEl.value = currentMax;

    const minLabel = document.querySelector(slider.dataset.targetMin);
    const maxLabel = document.querySelector(slider.dataset.targetMax);

    if (minLabel) minLabel.textContent = currentMin;
    if (maxLabel) maxLabel.textContent = currentMax;

    scheduleFetch();
  }

  function drag(e, thumb) {
    const pos = e.clientX - slider.getBoundingClientRect().left;
    const val = posToValue(pos);

    if (thumb === thumbs.min) {
      currentMin = Math.min(val, currentMax - STEP);
    } else {
      currentMax = Math.max(val, currentMin + STEP);
    }

    update();
  }

  build();
  update();

  thumbs.min.addEventListener("mousedown", () => {
    active = thumbs.min;
    document.onmousemove = e => drag(e, active);
    document.onmouseup = () => document.onmousemove = null;
  });

  thumbs.max.addEventListener("mousedown", () => {
    active = thumbs.max;
    document.onmousemove = e => drag(e, active);
    document.onmouseup = () => document.onmousemove = null;
  });

  window.addEventListener("resize", update);
}