export default function initDoubleSlider(slider) {
    if (!slider) return;

    const MIN = Number(slider.dataset.min);
    const MAX = Number(slider.dataset.max);
    const STEP = Number(slider.dataset.step) || 1;

    let currentMin = MIN;
    let currentMax = MAX;

    slider.addEventListener("sliderReset", () => {
        currentMin = MIN;
        currentMax = MAX;
    });
}
