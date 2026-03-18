const minInput = document.getElementById("mileage-min");
const maxInput = document.getElementById("mileage-max");

const minDisplay = document.getElementById("mileage-min-value");
const maxDisplay = document.getElementById("mileage-max-value");

const STEP = 10000;

function format(value) {
  return Number(value).toLocaleString("fr-FR");
}

function updateValues() {
  let min = parseInt(minInput.value);
  let max = parseInt(maxInput.value);

  // Empêche croisement
  if (min > max - STEP) {
    min = max - STEP;
    minInput.value = min;
  }

  if (max < min + STEP) {
    max = min + STEP;
    maxInput.value = max;
  }

  minDisplay.textContent = format(min);
  maxDisplay.textContent = format(max);

  // 🔥 Hook AJAX ici
  triggerFilter();
}

minInput.addEventListener("input", updateValues);
maxInput.addEventListener("input", updateValues);

updateValues();

function triggerFilter() {
  const form = document.getElementById("filters-form");
  const formData = new FormData(form);

  // debug
  console.log(Object.fromEntries(formData));

  // futur :
  /*
	fetch('/ajax/filter', {
		method: 'POST',
		body: formData
	})
	.then(res => res.json())
	.then(data => {
		updateResults(data);
	});
	*/
}
