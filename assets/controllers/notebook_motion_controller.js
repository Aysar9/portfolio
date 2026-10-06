import { Controller } from '@hotwired/stimulus';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

export default class extends Controller {
    static targets = ['writing', 'intro', 'portrait', 'section'];

    connect() {
        this.originalWriting = new Map();
        this.sectionTweens = [];
        // Turbo keeps this marker in its snapshot: returning does not replay the intro.
        if (this.element.dataset.notebookMotionPlayed === 'true') return;

        this.media = gsap.matchMedia(this.element);
        this.media.add('(prefers-reduced-motion: no-preference)', () => {
            if (this.element.dataset.notebookMotionPlayed === 'true') return;
            this.element.dataset.notebookMotionPlayed = 'true';

            // The greeting writes in; page titles appear together for faster reading.
            const isHomepage = Boolean(this.element.querySelector('.notebook-hero'));
            const characters = isHomepage ? this.splitWriting() : [];
            const timeline = gsap.timeline({ defaults: { ease: 'power2.out' } });
            if (characters.length) timeline.from(characters, {
                opacity: 0,
                duration: 0.07,
                stagger: 0.45 / Math.max(1, characters.length - 1),
                ease: 'none',
                clearProps: 'opacity',
            }, 0.05);
            if (!isHomepage && this.writingTargets.length) timeline.from(this.writingTargets, {
                opacity: 0, y: 6, duration: 0.3,
                clearProps: 'opacity,transform',
            }, 0.05);
            if (this.introTargets.length) timeline.from(this.introTargets, {
                opacity: 0, y: 6, duration: 0.3, stagger: 0.035,
                clearProps: 'opacity,transform',
            }, 0.08);
            if (this.hasPortraitTarget) timeline.from(this.portraitTarget, {
                opacity: 0, y: -8, rotation: 2, duration: 0.45,
                clearProps: 'opacity,transform',
            }, 0.1);

            for (const section of this.sectionTargets) {
                const tween = gsap.from(section, {
                    // Scroll motion never dims text or hides unvisited sections.
                    y: 6, duration: 0.28, ease: 'power2.out',
                    clearProps: 'opacity,transform',
                    scrollTrigger: { trigger: section, start: 'top 90%', once: true },
                });
                this.sectionTweens.push({ section, tween });
            }
            return () => this.restoreWriting();
        });
    }

    splitWriting() {
        const characters = [];
        for (const target of this.writingTargets) {
            const text = target.textContent;
            this.originalWriting.set(target, text);
            const fragment = document.createDocumentFragment();
            // Keep words intact so letter spans do not introduce new line breaks.
            for (const part of text.split(/(\s+)/)) {
                if (/^\s*$/.test(part)) {
                    fragment.append(document.createTextNode(part));
                    continue;
                }
                const word = document.createElement('span');
                word.className = 'notebook-writing-word';
                for (const letter of Array.from(part)) {
                    const character = document.createElement('span');
                    character.textContent = letter;
                    word.append(character);
                    characters.push(character);
                }
                fragment.append(word);
            }
            target.replaceChildren(fragment);
        }
        return characters;
    }

    revealFocusedSection(event) {
        const section = event.target.closest('[data-notebook-motion-target~="section"]');
        const entry = this.sectionTweens.find(item => item.section === section);
        if (!entry) return;
        // Keyboard navigation must never land on an invisible animated section.
        entry.tween.scrollTrigger?.kill();
        entry.tween.kill();
        gsap.set(section, { clearProps: 'opacity,transform' });
    }

    restoreWriting() {
        this.originalWriting.forEach((text, target) => { target.textContent = text; });
        this.originalWriting.clear();
    }

    finish() {
        this.media?.revert();
        this.media = null;
        this.restoreWriting();
        this.sectionTweens = [];
    }

    disconnect() {
        this.finish();
    }
}
