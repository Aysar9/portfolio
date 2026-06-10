import { Controller } from '@hotwired/stimulus';

/*
 * AI assistant widget. Sends visitor questions to /api/chat (Gemini-backed),
 * keeps a short in-memory history, and renders the conversation.
 */
export default class extends Controller {
    static targets = ['launcher', 'panel', 'messages', 'input', 'submit'];
    static values = { endpoint: String, error: String };

    connect() {
        this.history = [];
    }

    toggle() {
        const open = this.panelTarget.hasAttribute('hidden');
        this.panelTarget.toggleAttribute('hidden', !open);
        this.launcherTarget.toggleAttribute('hidden', open);
        this.launcherTarget.setAttribute('aria-expanded', String(open));
        if (open) {
            this.inputTarget.focus();
        }
    }

    async send(event) {
        event.preventDefault();
        const text = this.inputTarget.value.trim();
        if (text === '') {
            return;
        }

        this.appendMessage('user', text);
        this.inputTarget.value = '';
        this.setBusy(true);
        const typing = this.appendTyping();

        try {
            const response = await fetch(this.endpointValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text, history: this.history }),
            });

            const data = await response.json().catch(() => ({}));
            typing.remove();

            if (response.ok && data.reply) {
                this.appendMessage('model', data.reply);
                this.history.push({ role: 'user', text });
                this.history.push({ role: 'model', text: data.reply });
            } else {
                this.appendMessage('error', this.errorValue);
            }
        } catch (e) {
            typing.remove();
            this.appendMessage('error', this.errorValue);
        } finally {
            this.setBusy(false);
            this.inputTarget.focus();
        }
    }

    appendMessage(role, text) {
        const bubble = document.createElement('div');
        if (role === 'user') {
            bubble.className = 'ml-auto max-w-[85%] rounded-md bg-primary px-3 py-2 text-white';
        } else if (role === 'error') {
            bubble.className = 'max-w-[85%] rounded-md bg-canvas px-3 py-2 text-danger';
        } else {
            bubble.className = 'max-w-[85%] rounded-md bg-canvas px-3 py-2 text-ink/80';
        }
        bubble.textContent = text;
        this.messagesTarget.appendChild(bubble);
        this.scrollToBottom();
        return bubble;
    }

    appendTyping() {
        const bubble = document.createElement('div');
        bubble.className = 'max-w-[85%] rounded-md bg-canvas px-3 py-2 text-muted';
        bubble.textContent = '…';
        this.messagesTarget.appendChild(bubble);
        this.scrollToBottom();
        return bubble;
    }

    setBusy(busy) {
        this.submitTarget.disabled = busy;
        this.inputTarget.disabled = busy;
    }

    scrollToBottom() {
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
    }
}
