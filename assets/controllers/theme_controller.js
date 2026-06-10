import { Controller } from '@hotwired/stimulus';

/*
 * Dark/light theme toggle. Flips the `dark` class on <html> and persists the
 * choice in localStorage. The initial theme is applied by an inline script in
 * the <head> (before paint) to avoid a flash of the wrong theme.
 */
export default class extends Controller {
    static targets = ['sun', 'moon'];

    connect() {
        this.sync();
    }

    toggle() {
        const isDark = document.documentElement.classList.toggle('dark');
        try {
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        } catch (e) {
            /* storage unavailable — toggle still works for this page */
        }
        this.sync();
    }

    sync() {
        const isDark = document.documentElement.classList.contains('dark');
        if (this.hasSunTarget) {
            this.sunTarget.classList.toggle('hidden', !isDark);
        }
        if (this.hasMoonTarget) {
            this.moonTarget.classList.toggle('hidden', isDark);
        }
    }
}
