import { Controller } from '@hotwired/stimulus';

/*
 * Mobile navbar toggle.
 * Shows/hides the collapsed menu on small screens and keeps the
 * hamburger/close icons and aria-expanded state in sync.
 */
export default class extends Controller {
    static targets = ['menu', 'button', 'openIcon', 'closeIcon'];

    toggle() {
        const nowHidden = this.menuTarget.classList.toggle('hidden');
        this.setState(!nowHidden);
    }

    close() {
        this.menuTarget.classList.add('hidden');
        this.setState(false);
    }

    setState(isOpen) {
        this.buttonTarget.setAttribute('aria-expanded', String(isOpen));
        this.openIconTarget.classList.toggle('hidden', isOpen);
        this.closeIconTarget.classList.toggle('hidden', !isOpen);
    }
}
