// Sélecteurs
const minInput = document.getElementById('mileage-min');
const maxInput = document.getElementById('mileage-max');

const minDisplay = document.getElementById('mileage-min-value');
const maxDisplay = document.getElementById('mileage-max-value');

const track = document.querySelector('.range-track');

// Constantes
const STEP = 10000;
const MIN = 0;
const MAX = 400000;

// Formatage français
function format(value) {
  return Number(value).toLocaleString('fr-FR');
}

// Met à jour la barre de sélection entre min et max
function updateTrack(min, max) {
  const percentMin = (min / MAX) * 100;
  const percentMax = (max / MAX) * 100;

  track.style.left = percentMin + '%';
  track.style.width = (percentMax - percentMin) + '%';
}

// Gestion dynamique du z-index pour que min soit visible
function updateZIndex(min, max) {
  if (min >= max - STEP) {
    minInput.style.zIndex = 4;  // min au-dessus
    maxInput.style.zIndex = 3;
  } else {
    minInput.style.zIndex = 3;
    maxInput.style.zIndex = 4;  // max au-dessus
  }
}

// Fonction principale
function updateValues() {
  let min = parseInt(minInput.value);
  let max = parseInt(maxInput.value);

  // Empêche croisement
  if (min > max - STEP) {
    min = Math.max(MIN, max - STEP);
    minInput.value = min;
  }

  if (max < min + STEP) {
    max = Math.min(MAX, min + STEP);
    maxInput.value = max;
  }

  // Sécurité globale
  min = Math.max(MIN, min);
  max = Math.min(MAX, max);

  // Affichage
  minDisplay.textContent = format(min);
  maxDisplay.textContent = format(max);

  updateTrack(min, max);
  updateZIndex(min, max);

  // Hook AJAX
  triggerFilter();
}

// Events
minInput.addEventListener('input', updateValues);
maxInput.addEventListener('input', updateValues);

// Initialisation
updateValues();

// ==========================
// 🔗 AJAX (prêt pour Symfony)
// ==========================
function triggerFilter() {
  const form = document.getElementById('filters-form');
  const formData = new FormData(form);

  // Debug
  console.log(Object.fromEntries(formData));

  // Futur fetch
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