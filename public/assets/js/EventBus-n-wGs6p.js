class EventBus {
    constructor() {
        this.events = {};
    }

    on(event, callback) {
        if (!this.events[event]) {
            this.events[event] = [];
        }

        this.events[event].push(callback);
    }

    emit(event, data = null) {
        if (!this.events[event]) return;

        this.events[event].forEach(cb => cb(data));
    }
}

export default new EventBus();
