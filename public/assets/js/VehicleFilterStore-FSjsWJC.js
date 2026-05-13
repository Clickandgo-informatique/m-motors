/**
 * Store central des filtres véhicules
 * Source unique de vérité côté frontend
 */

export default class VehicleFilterStore {
  constructor() {
    this.state = {
      q: null,
      page: 1,
      view: "grid",
      filters: {}
    };

    this.listeners = [];
  }

  /**
   * Abonnement aux changements d'état
   */
  subscribe(callback) {
    this.listeners.push(callback);
  }

  /**
   * Notifie les listeners
   */
  notify() {
    this.listeners.forEach(cb => cb(this.state));
  }

  /**
   * Mise à jour partielle de l'état
   */
  update(partial) {
    this.state = {
      ...this.state,
      ...partial,
      filters: {
        ...this.state.filters,
        ...(partial.filters || {})
      }
    };

    this.notify();
  }

  /**
   * Reset complet des filtres
   */
  reset() {
    this.state = {
      q: null,
      page: 1,
      view: "grid",
      filters: {}
    };

    this.notify();
  }

  /**
   * Retour état courant
   */
  getState() {
    return this.state;
  }
}
